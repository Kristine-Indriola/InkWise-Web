document.addEventListener("DOMContentLoaded", () => {
  const cardImage = document.getElementById("cardImage");
  const frontBtn = document.getElementById("frontBtn");
  const backBtn = document.getElementById("backBtn");
  const colorBtns = document.querySelectorAll(".color-btn");

  let currentFront = "front-black.jpg";
  let currentBack = "back-black.jpg";
  let showingFront = true;

  // Toggle Front/Back
  frontBtn.addEventListener("click", () => {
    showingFront = true;
    cardImage.src = currentFront;
    frontBtn.classList.add("active");
    backBtn.classList.remove("active");
  });

  backBtn.addEventListener("click", () => {
    showingFront = false;
    cardImage.src = currentBack;
    backBtn.classList.add("active");
    frontBtn.classList.remove("active");
  });

  // Change Colors
  colorBtns.forEach(btn => {
    btn.addEventListener("click", () => {
      colorBtns.forEach(b => b.classList.remove("active"));
      btn.classList.add("active");

      currentFront = btn.dataset.front;
      currentBack = btn.dataset.back;

      // Show correct side
      cardImage.src = showingFront ? currentFront : currentBack;
    });
  });
});
