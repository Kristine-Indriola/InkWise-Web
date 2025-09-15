document.addEventListener("DOMContentLoaded", () => {
  const frontBtn = document.getElementById("showFront");
  const backBtn = document.getElementById("showBack");
  const cardFront = document.getElementById("cardFront");
  const cardBack = document.getElementById("cardBack");

  const zoomInBtn = document.getElementById("zoomIn");
  const zoomOutBtn = document.getElementById("zoomOut");
  const zoomLevel = document.getElementById("zoomLevel");
  const canvas = document.querySelector(".canvas");

  const textFieldsContainer = document.getElementById("textFields");
  const addTextBtn = document.getElementById("addTextField");

  let zoom = 1.0; // default zoom

  /* ========== FRONT/BACK TOGGLE ========== */
  frontBtn.addEventListener("click", () => {
    cardFront.classList.add("active");
    cardBack.classList.remove("active");
    frontBtn.classList.add("active");
    backBtn.classList.remove("active");
  });

  backBtn.addEventListener("click", () => {
    cardBack.classList.add("active");
    cardFront.classList.remove("active");
    backBtn.classList.add("active");
    frontBtn.classList.remove("active");
  });

  /* ========== ZOOM CONTROLS ========== */
  zoomInBtn.addEventListener("click", () => {
    if (zoom < 2) {
      zoom += 0.1;
      updateZoom();
    }
  });

  zoomOutBtn.addEventListener("click", () => {
    if (zoom > 0.5) {
      zoom -= 0.1;
      updateZoom();
    }
  });

  function updateZoom() {
    canvas.style.transform = `scale(${zoom})`;
    canvas.style.transformOrigin = "center center";
    zoomLevel.textContent = `${Math.round(zoom * 100)}%`;
  }

  /* ========== ADD TEXT FIELD ========== */
  addTextBtn.addEventListener("click", () => {
    const wrapper = document.createElement("div");
    wrapper.classList.add("text-field");

    const input = document.createElement("input");
    input.type = "text";
    input.value = "New Text";

    const delBtn = document.createElement("button");
    delBtn.classList.add("delete-text");
    delBtn.textContent = "🗑";

    // delete action
    delBtn.addEventListener("click", () => {
      wrapper.remove();
    });

    wrapper.appendChild(input);
    wrapper.appendChild(delBtn);
    textFieldsContainer.appendChild(wrapper);
  });

  /* ========== DELETE EXISTING TEXT FIELDS ========== */
  document.querySelectorAll(".delete-text").forEach(btn => {
    btn.addEventListener("click", (e) => {
      e.target.closest(".text-field").remove();
    });
  });
});
