// rupiah-format.js
document.addEventListener("DOMContentLoaded", function () {
    const inputJumlah = document.getElementById("jumlah");

    if (inputJumlah) {
        inputJumlah.addEventListener("input", function (e) {
            let value = e.target.value.replace(/\D/g, ""); // Hanya angka
            if (value !== "") {
                e.target.value = new Intl.NumberFormat("id-ID").format(value);
            } else {
                e.target.value = "";
            }
        });
    }
});
