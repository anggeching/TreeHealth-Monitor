document.addEventListener("DOMContentLoaded", function () {
    const carousel = document.getElementById("carouselCaptions");
    const titleElement = document.getElementById("carouselTitle");
    const textElement = document.getElementById("carouselText");

    carousel.addEventListener("slid.bs.carousel", function () {
        const activeItem = carousel.querySelector(".carousel-item.active");
        titleElement.textContent = activeItem.getAttribute("data-title");
        textElement.textContent = activeItem.getAttribute("data-text");
    });
});

document.addEventListener("DOMContentLoaded", function () {
    let activeSlide = document.querySelector(".carousel-item.active");
    document.getElementById("carouselTitle").textContent = activeSlide.getAttribute("data-title");
    document.getElementById("carouselText").textContent = activeSlide.getAttribute("data-text");
});

document.getElementById("carouselCaptions").addEventListener("slid.bs.carousel", function (event) {
    let activeSlide = event.relatedTarget;
    document.getElementById("carouselTitle").textContent = activeSlide.getAttribute("data-title");
    document.getElementById("carouselText").textContent = activeSlide.getAttribute("data-text");
});

//----------timing--------
document.addEventListener("DOMContentLoaded", function () {
    let carouselElement = document.querySelector("#carouselCaptions");
    let carousel = new bootstrap.Carousel(carouselElement, {
        interval: 10000, // Images change every 10 seconds
        pause: "hover",
        wrap: true
    });
});