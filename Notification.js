document.addEventListener("DOMContentLoaded", function() {
    const lineNotificationsCheckbox = document.getElementById("line-notifications");
    const lineIdInput = document.getElementById("line-id");

    lineNotificationsCheckbox.addEventListener("change", function() {
        if (this.checked) {
            lineIdInput.disabled = false;
        } else {
            lineIdInput.disabled = true;
            lineIdInput.value = "";
        }
    });

    const form = document.getElementById("notification-settings-form");

    form.addEventListener("submit", function(event) {
        event.preventDefault();

        const emailNotifications = document.getElementById("email-notifications").checked;
        const lineNotifications = document.getElementById("line-notifications").checked;
        const lineId = document.getElementById("line-id").value;

        const settingsData = {
            emailNotifications: emailNotifications,
            lineNotifications: lineNotifications,
            lineId: lineId
        };

        console.log("Notification settings saved:", settingsData);
        alert("Notification settings saved successfully!");

        // Here, you would typically send this data to the server using an AJAX request or fetch API.
    });
});
