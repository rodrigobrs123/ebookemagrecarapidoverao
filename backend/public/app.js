const loja = document.querySelector("#loja");
const statusBox = document.querySelector("#status");
const caminho = location.pathname.replace(/\/$/, "") || "/";
const botoes = [document.querySelector("#pagar-topo"), document.querySelector("#pagar")].filter(Boolean);
const textoBotao = "Quero o meu PDF por R$ 12,99";
const chavePedido = "pedidoEbookVerao";

function definirBotoes(desabilitado, texto) {
  botoes.forEach((botao) => {
    botao.disabled = desabilitado;
    botao.textContent = texto;
  });
}

async function consultarPedido(codigo) {
  const resposta = await fetch("/api/orders/" + codigo, { headers: { "Accept": "application/json" } });
  if (!resposta.ok) return null;
  return resposta.json();
}

function irParaDownload(codigo) {
  if (caminho === "/pagamento/" + codigo) return;
  location.assign("/pagamento/" + codigo);
}

function liberarEspera() {
  sessionStorage.removeItem(chavePedido);
  definirBotoes(false, textoBotao);
  const espera = document.querySelector("#espera");
  if (espera) espera.hidden = true;
}

function aguardarPagamento(codigo) {
  const espera = document.querySelector("#espera");
  if (espera) espera.hidden = false;
  definirBotoes(true, "Aguardando o PIX...");

  const verificar = async () => {
    const pedido = await consultarPedido(codigo);
    if (pedido?.paid) {
      sessionStorage.removeItem(chavePedido);
      irParaDownload(codigo);
      return true;
    }
    if (pedido?.status === "canceled" || pedido?.status === "expired") {
      sessionStorage.removeItem(chavePedido);
      definirBotoes(false, textoBotao);
      if (espera) espera.hidden = true;
      const erro = document.querySelector("#erro");
      if (erro) {
        erro.hidden = false;
        erro.textContent = pedido.status === "expired"
          ? "O tempo do PIX acabou. Gere um novo link."
          : "O pagamento foi cancelado. Gere um novo link.";
      }
      return true;
    }
    return false;
  };

  verificar().then((pronto) => {
    if (pronto) return;
    const timer = setInterval(async () => {
      if (await verificar()) clearInterval(timer);
    }, 3000);
  });
}

async function abrirPagamento(evento) {
  evento.preventDefault();
  const erro = document.querySelector("#erro");
  const espera = document.querySelector("#espera");
  erro.hidden = true;
  if (espera) espera.hidden = true;
  definirBotoes(true, "Abrindo o PIX...");

  const janelaPix = window.open("about:blank", "asaas-pix");

  try {
    const resposta = await fetch("/api/checkout", {
      method: "POST",
      headers: { "Content-Type": "application/json", "Accept": "application/json" },
      body: JSON.stringify({ origin: location.origin }),
    });
    const dados = await resposta.json().catch(() => ({}));
    if (!resposta.ok || !dados.checkoutUrl || !dados.publicId) {
      throw new Error(mensagemCliente(dados.message || ("Erro HTTP " + resposta.status)));
    }

    sessionStorage.setItem(chavePedido, dados.publicId);
    if (janelaPix && !janelaPix.closed) {
      janelaPix.location.href = dados.checkoutUrl;
      janelaPix.focus();
    } else {
      window.open(dados.checkoutUrl, "asaas-pix");
    }
    aguardarPagamento(dados.publicId);
  } catch (falha) {
    if (janelaPix && !janelaPix.closed) janelaPix.close();
    erro.hidden = false;
    erro.textContent = falha.message;
    erro.scrollIntoView({ block: "center" });
    definirBotoes(false, textoBotao);
  }
}

function mensagemCliente(texto) {
  const bruto = String(texto || "");
  if (!bruto || /ngrok|webhook|localhost|URL pública/i.test(bruto)) {
    return "Não foi possível abrir o pagamento. Tente de novo.";
  }
  return bruto;
}

document.querySelector("#formulario")?.addEventListener("submit", abrirPagamento);
document.querySelector("#pagar-topo")?.addEventListener("click", abrirPagamento);
document.querySelector("#novo-pix")?.addEventListener("click", (evento) => {
  evento.preventDefault();
  liberarEspera();
});

if (caminho.startsWith("/pagamento/")) {
  loja.hidden = true;
  statusBox.hidden = false;
  const codigo = caminho.split("/").pop();
  const titulo = document.querySelector("#status-titulo");
  const texto = document.querySelector("#status-texto");
  const baixar = document.querySelector("#baixar");

  if (codigo === "cancelado") {
    titulo.textContent = "Pagamento cancelado";
    texto.textContent = "O checkout foi cancelado. O PDF continua bloqueado.";
  } else if (codigo === "expirado") {
    titulo.textContent = "Checkout expirado";
    texto.textContent = "O tempo do PIX acabou. Gere um novo link na página inicial.";
  } else {
    const consultar = async () => {
      const pedido = await consultarPedido(codigo);
      if (!pedido) {
        titulo.textContent = "Pedido não encontrado";
        texto.textContent = "Esse link de retorno não corresponde a uma compra.";
        return true;
      }
      if (pedido.paid && pedido.downloadPath) {
        sessionStorage.removeItem(chavePedido);
        titulo.textContent = "Pagamento confirmado";
        texto.textContent = "O PIX foi recebido. O botão abaixo abre o PDF do e-book.";
        baixar.href = pedido.downloadPath;
        baixar.hidden = false;
        return true;
      }
      titulo.textContent = "Aguardando o PIX";
      texto.textContent = "O pagamento ainda está sendo confirmado. Esta página libera o PDF assim que o PIX entrar.";
      return false;
    };
    consultar().then((pago) => {
      if (!pago) {
        const timer = setInterval(async () => {
          if (await consultar()) clearInterval(timer);
        }, 3000);
      }
    });
  }
} else {
  const pedidoAberto = sessionStorage.getItem(chavePedido);
  if (pedidoAberto) aguardarPagamento(pedidoAberto);
}
