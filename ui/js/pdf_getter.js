function post_pdf(pdf_id) {

    idf_ = $('#'+pdf_id+'').data("idf");

    console.log(idf_);
    $.ajax({
        type: "POST",
        url: "view_pdf",
        data: {textdata:pdf_id,idf:idf_},
        success: function (data) {
            console.log('PDF post was successful.');
            toggle_overlay();
            $("#overlay").html(data);
            scrollUp();
        },
        error: function (data) {
            console.log('An error occurred.');
            console.log(data);
        },
    });
}

function toggle_overlay() {
    let content = document.getElementById("content");
    let overlay = document.getElementById("overlay");
    if (content.style.display === "none") {
        content.style.display = "block";
        overlay.style.display = "none";
    } else {
        content.style.display = "none";
        overlay.style.display = "block";
    }
}