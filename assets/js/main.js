document.addEventListener("DOMContentLoaded", function() {
    const searchBtn = document.getElementById('search-btn');
    const searchInput = document.getElementById('search-input');
    const searchForm = document.getElementById('search-form');

    if(searchBtn && searchInput) {
        searchBtn.addEventListener('click', function() {
            if (!searchInput.classList.contains('active')) {
                // Mở thanh tìm kiếm
                searchInput.classList.add('active');
                searchInput.focus();
            } else {
                // Nếu đã mở và có nhập nội dung -> Thực hiện tìm kiếm
                if (searchInput.value.trim() !== "") {
                    searchForm.submit();
                } else {
                    // Nếu để trống mà click lại -> Đóng thanh tìm kiếm
                    searchInput.classList.remove('active');
                }
            }
        });

        // Tự động thu gọn khi click ra ngoài vùng tìm kiếm
        document.addEventListener('click', function(event) {
            if (!searchForm.contains(event.target) && searchInput.classList.contains('active')) {
                if (searchInput.value.trim() === "") {
                    searchInput.classList.remove('active');
                }
            }
        });
    }
});