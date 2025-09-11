document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector(".create-form");
    const designInput = document.getElementById("design");

    // For now, set some sample JSON design data
    form.addEventListener("submit", function () {
        designInput.value = JSON.stringify({
            text: "Please join us...",
            bride: "ELEANOR",
            groom: "VINCENT",
            date: "25 October 2025",
        });
    });
});


