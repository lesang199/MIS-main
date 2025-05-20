// scripts.js
document.addEventListener("DOMContentLoaded", function() {
    const moviesContainer = document.querySelector('.movie-list');
    
    // Tạo một danh sách phim mẫu
    const movies = [
        { title: 'Avengers: Endgame', description: 'Phim hành động', image: 'movie1.jpg' },
        { title: 'Jumanji', description: 'Phim phiêu lưu', image: 'movie2.jpg' },
        { title: 'The Lion King', description: 'Phim hoạt hình', image: 'movie3.jpg' }
    ];

    // Hiển thị các bộ phim lên trang
    movies.forEach(function(movie) {
        const movieElement = document.createElement('div');
        movieElement.classList.add('movie');
        movieElement.innerHTML = `<img src="${movie.image}" alt="${movie.title}">
                                  <h2>${movie.title}</h2>
                                  <p>${movie.description}</p>`;
        moviesContainer.appendChild(movieElement);
    });
});
// Thêm sự kiện click cho các bộ phim
    moviesContainer.addEventListener('click', function(event) {
        if (event.target.closest('.movie')) {
            const movieTitle = event.target.closest('.movie').querySelector('h2').textContent;
            alert(`Bạn đã chọn bộ phim: ${movieTitle}`);
        }
    });
;    