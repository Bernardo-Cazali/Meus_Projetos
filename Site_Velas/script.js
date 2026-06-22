document.addEventListener("DOMContentLoaded", () => {

    const header = document.querySelector("header");
    window.addEventListener("scroll", () => {
        if (window.scrollY > 50) {
            header.style.padding = "10px 8%";
            header.style.backgroundColor = "rgba(253, 251, 247, 0.98)";
        } else {
            header.style.padding = "20px 8%";
            header.style.backgroundColor = "rgba(253, 251, 247, 0.95)";
        }
    });

});