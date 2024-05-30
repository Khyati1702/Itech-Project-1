document.addEventListener("DOMContentLoaded", function() {
    const comments = [
        "Great job on the assignment! Keep up the good work.",
        "Please see me after class to discuss your submission.",
        "Well done, but there's room for improvement in your analysis."
    ];

    const commentsElement = document.getElementById("comments");
    commentsElement.innerHTML = comments.join("<br>");

    // Simulate notification for new comment
    setTimeout(function() {
        alert("New comment added by your teacher.");
    }, 3000); // 3 seconds delay for demo
});
