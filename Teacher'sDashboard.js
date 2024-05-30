document.addEventListener("DOMContentLoaded", function() {
    const students = [
        {
            id: 1,
            name: "John Doe",
            contact: "123-456-7890",
            email: "john.doe@example.com",
            courses: ["Math", "Science", "History"],
            schedule: "Mon, Wed, Fri"
        },
        {
            id: 2,
            name: "Jane Smith",
            contact: "987-654-3210",
            email: "jane.smith@example.com",
            courses: ["English", "Art", "Math"],
            schedule: "Tue, Thu"
        },
        {
            id: 3,
            name: "Alice Johnson",
            contact: "555-555-5555",
            email: "alice.johnson@example.com",
            courses: ["Physics", "Chemistry", "Biology"],
            schedule: "Mon, Wed, Fri"
        }
    ];

    const studentsTableBody = document.querySelector("#students-table tbody");
    students.forEach(student => {
        const row = document.createElement("tr");

        const nameCell = document.createElement("td");
        const nameLink = document.createElement("a");
        nameLink.href = `student.html?id=${student.id}`;
        nameLink.textContent = student.name;
        nameCell.appendChild(nameLink);

        const idCell = document.createElement("td");
        idCell.textContent = student.id;

        const contactCell = document.createElement("td");
        contactCell.textContent = `${student.contact} (${student.email})`;

        const coursesCell = document.createElement("td");
        coursesCell.textContent = student.courses.join(", ");

        const scheduleCell = document.createElement("td");
        scheduleCell.textContent = student.schedule;

        row.appendChild(nameCell);
        row.appendChild(idCell);
        row.appendChild(contactCell);
        row.appendChild(coursesCell);
        row.appendChild(scheduleCell);

        studentsTableBody.appendChild(row);
    });
});
