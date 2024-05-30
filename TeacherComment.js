document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById("comments-form");

    form.addEventListener("submit", function(event) {
        event.preventDefault();

        const studentId = document.getElementById("student-id").value;
        const course = document.getElementById("course").value;
        const examComment = document.getElementById("exam-comment").value;
        const assignment1Comment = document.getElementById("assignment1-comment").value;
        const assignment2Comment = document.getElementById("assignment2-comment").value;
        const assignment3Comment = document.getElementById("assignment3-comment").value;
        const assignment4Comment = document.getElementById("assignment4-comment").value;

        const commentData = {
            studentId: studentId,
            course: course,
            examComment: examComment,
            assignments: [
                { name: "Assignment 1", comment: assignment1Comment },
                { name: "Assignment 2", comment: assignment2Comment },
                { name: "Assignment 3", comment: assignment3Comment },
                { name: "Assignment 4", comment: assignment4Comment }
            ]
        };

        console.log("Comments sent:", commentData);
        alert("Comments sent successfully!");

        // Here, you would typically send this data to the server using an AJAX request or fetch API.
    });
});
