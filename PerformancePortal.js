document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const studentId = urlParams.get("id");

    const studentData = {
        1: {
            id: 1,
            name: "John Doe",
            grades: {
                "SACE Japanese Background Speaker Stage 1": "A",
                "SACE Japanese Background Speaker Stage 2": "B+",
                "SACE Japanese Continuers Stage 1": "A-",
                "SACE Japanese Continuers Stage 2": "B"
            },
            attendance: {
                "SACE Japanese Background Speaker Stage 1": "95%",
                "SACE Japanese Background Speaker Stage 2": "90%",
                "SACE Japanese Continuers Stage 1": "85%",
                "SACE Japanese Continuers Stage 2": "80%"
            },
            participation: {
                "SACE Japanese Background Speaker Stage 1": "High",
                "SACE Japanese Background Speaker Stage 2": "Medium",
                "SACE Japanese Continuers Stage 1": "High",
                "SACE Japanese Continuers Stage 2": "Medium"
            }
        },
        2: {
            id: 2,
            name: "Jane Smith",
            grades: {
                "SACE Japanese Background Speaker Stage 1": "B",
                "SACE Japanese Background Speaker Stage 2": "A",
                "SACE Japanese Continuers Stage 1": "B+",
                "SACE Japanese Continuers Stage 2": "A-"
            },
            attendance: {
                "SACE Japanese Background Speaker Stage 1": "92%",
                "SACE Japanese Background Speaker Stage 2": "88%",
                "SACE Japanese Continuers Stage 1": "94%",
                "SACE Japanese Continuers Stage 2": "90%"
            },
            participation: {
                "SACE Japanese Background Speaker Stage 1": "Medium",
                "SACE Japanese Background Speaker Stage 2": "High",
                "SACE Japanese Continuers Stage 1": "Medium",
                "SACE Japanese Continuers Stage 2": "High"
            }
        },
        3: {
            id: 3,
            name: "Alice Johnson",
            grades: {
                "SACE Japanese Background Speaker Stage 1": "A-",
                "SACE Japanese Background Speaker Stage 2": "A",
                "SACE Japanese Continuers Stage 1": "B+",
                "SACE Japanese Continuers Stage 2": "A-"
            },
            attendance: {
                "SACE Japanese Background Speaker Stage 1": "89%",
                "SACE Japanese Background Speaker Stage 2": "93%",
                "SACE Japanese Continuers Stage 1": "91%",
                "SACE Japanese Continuers Stage 2": "92%"
            },
            participation: {
                "SACE Japanese Background Speaker Stage 1": "High",
                "SACE Japanese Background Speaker Stage 2": "High",
                "SACE Japanese Continuers Stage 1": "Medium",
                "SACE Japanese Continuers Stage 2": "High"
            }
        }
    };

    const student = studentData[studentId];

    if (student) {
        document.getElementById("student-id").textContent = student.id;
        document.getElementById("student-name").textContent = student.name;

        const gradesTableBody = document.querySelector("#grades-table tbody");
        Object.keys(student.grades).forEach(course => {
            const row = document.createElement("tr");
            const courseCell = document.createElement("td");
            courseCell.textContent = course;
            const gradeCell = document.createElement("td");
            gradeCell.textContent = student.grades[course];
            row.appendChild(courseCell);
            row.appendChild(gradeCell);
            gradesTableBody.appendChild(row);
        });

        const attendanceTableBody = document.querySelector("#attendance-table tbody");
        Object.keys(student.attendance).forEach(course => {
            const row = document.createElement("tr");
            const courseCell = document.createElement("td");
            courseCell.textContent = course;
            const attendanceCell = document.createElement("td");
            attendanceCell.textContent = student.attendance[course];
            row.appendChild(courseCell);
            row.appendChild(attendanceCell);
            attendanceTableBody.appendChild(row);
        });

        const participationTableBody = document.querySelector("#participation-table tbody");
        Object.keys(student.participation).forEach(course => {
            const row = document.createElement("tr");
            const courseCell = document.createElement("td");
            courseCell.textContent = course;
            const participationCell = document.createElement("td");
            participationCell.textContent = student.participation[course];
            row.appendChild(courseCell);
            row.appendChild(participationCell);
            participationTableBody.appendChild(row);
        });
    } else {
        alert("Student not found");
    }
});
