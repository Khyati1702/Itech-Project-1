document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById("score-form");

    form.addEventListener("submit", function(event) {
        event.preventDefault();

        const studentId = document.getElementById("student-id").value;
        const course = document.getElementById("course").value;
        const examScore = document.getElementById("exam-score").value;
        const assignment1Score = document.getElementById("assignment1-score").value;
        const assignment2Score = document.getElementById("assignment2-score").value;
        const assignment3Score = document.getElementById("assignment3-score").value;
        const assignment4Score = document.getElementById("assignment4-score").value;

        if (
            examScore < 0 || examScore > 100 ||
            assignment1Score < 0 || assignment1Score > 100 ||
            assignment2Score < 0 || assignment2Score > 100 ||
            assignment3Score < 0 || assignment3Score > 100 ||
            assignment4Score < 0 || assignment4Score > 100
        ) {
            alert("Scores must be between 0 and 100.");
            return;
        }

        const scoreData = {
            studentId: studentId,
            course: course,
            examScore: examScore,
            assignments: [
                { name: "Assignment 1", score: assignment1Score },
                { name: "Assignment 2", score: assignment2Score },
                { name: "Assignment 3", score: assignment3Score },
                { name: "Assignment 4", score: assignment4Score }
            ]
        };

        console.log("Scores saved:", scoreData);
        alert("Scores saved successfully!");

        // Here, you would typically send this data to the server using an AJAX request or fetch API.
    });
});
