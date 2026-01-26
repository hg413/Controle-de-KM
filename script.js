const canvas = document.getElementById("assinatura");
const ctx = canvas.getContext("2d");
let desenho = false;

canvas.addEventListener("mousedown", () => desenhando = true);
canvas.addEventListener("mouseup", () => desenhando = false);
canvas.addEventListener("mousemove", desenhar);