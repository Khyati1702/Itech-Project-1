document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const studentId = urlParams.get("id");

    const studentData = {
        1: {
            id: 1,
            name: "John Doe",
            contact_number: "123-456-7890",
            email: "john.doe@example.com",
            address: "123 Main St, Anytown, USA",
            dob: "2000-01-01",
            enrollment_date: "2018-09-01"
        },
        2: {
            id: 2,
            name: "Jane Smith",
            contact_number: "987-654-3210",
            email: "jane.smith@example.com",
            address: "456 Elm St, Othertown, USA",
            dob: "2001-02-02",
            enrollment_date: "2019-09-01"
        },
        3: {
            id: 3,
            name: "Alice Johnson",
            contact_number: "555-555-5555",
            email: "alice.johnson@example.com",
            address: "789 Oak St, Sometown, USA",
            dob: "2002-03-03",
            enrollment_date: "2020-09-01"
        }
    };

    const student = studentData[studentId];

    if (student) {
        document.getElementById("student-id").textContent = student.id;
        document.getElementById("student-name").textContent = student.name;
        document.getElementById("contact-number").textContent = student.contact_number;
        document.getElementById("email").textContent = student.email;
        document.getElementById("address").textContent = student.address;
        document.getElementById("dob").textContent = student.dob;
        document.getElementById("enrollment-date").textContent = student.enrollment_date;
    } else {
        alert("Student not found");
    }
});
