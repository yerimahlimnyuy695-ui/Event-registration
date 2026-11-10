<?php

session_start();

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "event_registration_db"
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$errors = [];
$success = "";
$db_error = "";

$participant_name = "";
$email = "";
$phone = "";
$workshop = "";
$attendance_mode = "";
$number_of_seats = "";
$special_requirements = "";

$workshops = [
    "Web Development Workshop",
    "Python Programming Workshop",
    "Database Management Workshop",
    "Cybersecurity Workshop",
    "Artificial Intelligence Workshop",
    "Mobile App Development Workshop"
];

if (isset($_SESSION["success"])) {

    $success = $_SESSION["success"];

    unset($_SESSION["success"]);

}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $participant_name = trim($_POST["participant_name"] ?? "");

    $email = trim($_POST["email"] ?? "");

    $phone = trim($_POST["phone"] ?? "");

    $workshop = trim($_POST["workshop"] ?? "");

    $attendance_mode = trim($_POST["attendance_mode"] ?? "");

    $number_of_seats = trim($_POST["number_of_seats"] ?? "");

    $special_requirements = trim($_POST["special_requirements"] ?? "");

    $name_length = function_exists("mb_strlen")
        ? mb_strlen($participant_name, "UTF-8")
        : strlen($participant_name);

    if (empty($participant_name)) {

        $errors["participant_name"] =
            "Participant name is required.";

    } elseif ($name_length < 2 || $name_length > 100) {

        $errors["participant_name"] =
            "Name must contain between 2 and 100 characters.";

    }

    if (empty($email)) {

        $errors["email"] = "Email is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors["email"] = "Enter a valid email address.";

    } elseif (strlen($email) > 150) {

        $errors["email"] = "Email address is too long.";

    }

    if (strlen($phone) > 20) {

        $errors["phone"] = "Phone number is too long.";

    }

    if (!in_array($workshop, $workshops, true)) {

        $errors["workshop"] =
            "Please select a valid workshop.";

    }

    $attendance_options = [
        "Option A",
        "Option B"
    ];

    if (!in_array($attendance_mode, $attendance_options, true)) {

        $errors["attendance_mode"] =
            "Please select an attendance mode.";

    }

    if ($number_of_seats === "") {

        $errors["number_of_seats"] =
            "Number of seats is required.";

    } elseif (
        !preg_match('/^[1-3]$/', $number_of_seats)
    ) {

        $errors["number_of_seats"] =
            "Number of seats must be an integer between 1 and 3.";

    }

    $requirements_length = function_exists("mb_strlen")
        ? mb_strlen($special_requirements, "UTF-8")
        : strlen($special_requirements);

    if ($requirements_length > 300) {

        $errors["special_requirements"] =
            "Special requirements cannot exceed 300 characters.";

    }

    if (($_POST["terms"] ?? "") !== "1") {

        $errors["terms"] =
            "You must accept the event terms.";

    }

    if (
        empty($errors) &&
        !empty($email) &&
        !empty($workshop)
    ) {

        $check = $conn->prepare(
            "SELECT registration_id
             FROM registrations
             WHERE email = ? AND workshop = ?"
        );

        $check->bind_param(
            "ss",
            $email,
            $workshop
        );

        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $errors["duplicate"] =
                "This email has already been registered for this workshop.";

        }

        $check->close();

    }

    if (empty($errors)) {

        $seats = (int)$number_of_seats;

        $terms_accepted = 1;

        $sql = "INSERT INTO registrations
        (
            participant_name,
            email,
            phone,
            workshop,
            attendance_mode,
            number_of_seats,
            special_requirements,
            terms_accepted
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "sssssisi",
                $participant_name,
                $email,
                $phone,
                $workshop,
                $attendance_mode,
                $seats,
                $special_requirements,
                $terms_accepted
            );


            if ($stmt->execute()) {

                $_SESSION["success"] =
                    "Registration completed successfully!";

                header("Location: index.php");

                exit();

            } else {

                if ($stmt->errno == 1062) {

                    $errors["duplicate"] =
                        "This email has already been registered for this workshop.";

                } else {

                    $db_error =
                        "Unable to save registration. Please try again.";

                }

            }

            $stmt->close();

        } else {

            $db_error =
                "Unable to prepare database query.";

        }

    }

}

$registrations = $conn->query(
    "SELECT * FROM registrations
     ORDER BY registration_date DESC"
);

$workshop_totals = $conn->query(
    "SELECT
        workshop,
        COUNT(*) AS total_registrations,
        SUM(number_of_seats) AS total_seats
     FROM registrations
     GROUP BY workshop
     ORDER BY workshop ASC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LAB 05 - Event Registration</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

    <h2>Event Registration</h2>

    <p>Registration for events</p>

    <form action="" method="POST">

        <div class="form-header">
            Please fill in the form to register
        </div>

        <div class="form-group">

            <label>Participant name</label>

            <input
                type="text"
                name="participant_name"
                value="<?php echo htmlspecialchars($participant_name); ?>"
            >

            <?php if (isset($errors["participant_name"])): ?>

                <p class="error">
                    <?php echo htmlspecialchars($errors["participant_name"]); ?>
                </p>

            <?php endif; ?>

        </div>

        <div class="form-group">

            <label>Email</label>

            <input
                type="email"
                name="email"
                value="<?php echo htmlspecialchars($email); ?>"
            >

            <?php if (isset($errors["email"])): ?>

                <p class="error">
                    <?php echo htmlspecialchars($errors["email"]); ?>
                </p>

            <?php endif; ?>

        </div>

        <div class="form-group">

            <label>Phone</label>

            <input
                type="tel"
                name="phone"
                value="<?php echo htmlspecialchars($phone); ?>"
            >

            <?php if (isset($errors["phone"])): ?>

                <p class="error">
                    <?php echo htmlspecialchars($errors["phone"]); ?>
                </p>

            <?php endif; ?>

        </div>

        <div class="form-group">

            <label>Workshop</label>

            <select name="workshop">

                <option value="">Choose an option</option>

                <?php foreach ($workshops as $item): ?>

                    <option
                        value="<?php echo htmlspecialchars($item); ?>"
                        <?php if ($workshop == $item) echo "selected"; ?>
                    >

                        <?php echo htmlspecialchars($item); ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <?php if (isset($errors["workshop"])): ?>

                <p class="error">
                    <?php echo htmlspecialchars($errors["workshop"]); ?>
                </p>

            <?php endif; ?>

        </div>

        <div class="form-group">

            <label>Attendance mode</label>

            <div class="radio-group">

                <label class="radio-label">

                    <input
                        type="radio"
                        name="attendance_mode"
                        value="Option A"
                        <?php if ($attendance_mode == "Option A") echo "checked"; ?>
                    >

                    Option A

                </label>


                <label class="radio-label">

                    <input
                        type="radio"
                        name="attendance_mode"
                        value="Option B"
                        <?php if ($attendance_mode == "Option B") echo "checked"; ?>
                    >

                    Option B

                </label>

            </div>

            <?php if (isset($errors["attendance_mode"])): ?>

                <p class="error">
                    <?php echo htmlspecialchars($errors["attendance_mode"]); ?>
                </p>

            <?php endif; ?>

        </div>

        <div class="form-group">

            <label>Number of seats</label>

            <input
                type="number"
                name="number_of_seats"
                min="1"
                max="3"
                step="1"
                value="<?php echo htmlspecialchars($number_of_seats); ?>"
            >

            <?php if (isset($errors["number_of_seats"])): ?>

                <p class="error">
                    <?php echo htmlspecialchars($errors["number_of_seats"]); ?>
                </p>

            <?php endif; ?>

        </div>

        <div class="form-group">

            <label>Special requirements</label>

            <input
                type="text"
                name="special_requirements"
                maxlength="300"
                value="<?php echo htmlspecialchars($special_requirements); ?>"
            >

            <?php if (isset($errors["special_requirements"])): ?>

                <p class="error">
                    <?php echo htmlspecialchars($errors["special_requirements"]); ?>
                </p>

            <?php endif; ?>

        </div>

        <div class="form-group">

            <label>Accept event terms</label>

            <div>

                <input
                    type="checkbox"
                    name="terms"
                    value="1"
                    <?php if (($_POST["terms"] ?? "") === "1") echo "checked"; ?>
                >

                I agree

            </div>

            <?php if (isset($errors["terms"])): ?>

                <p class="error">
                    <?php echo htmlspecialchars($errors["terms"]); ?>
                </p>

            <?php endif; ?>

        </div>

        <div class="form-buttons">

            <button type="submit">Submit Form</button>

            <button type="reset">Reset</button>

        </div>

    </form>

    <?php if (isset($errors["duplicate"])): ?>

        <p class="error">
            <?php echo htmlspecialchars($errors["duplicate"]); ?>
        </p>

    <?php endif; ?>

    <?php if ($db_error != ""): ?>

        <p class="error">
            <?php echo htmlspecialchars($db_error); ?>
        </p>

    <?php endif; ?>

    <?php if ($success != ""): ?>

        <script>

            alert(<?php echo json_encode($success); ?>);

        </script>

    <?php endif; ?>
    <h2>Registered Participants</h2>


    <table>

        <thead>

            <tr>

                <th>Registration ID</th>

                <th>Participant Name</th>

                <th>Email</th>

                <th>Phone</th>

                <th>Workshop</th>

                <th>Attendance Mode</th>

                <th>Seats</th>

                <th>Special Requirements</th>

                <th>Registration Date</th>

            </tr>

        </thead>


        <tbody>

            <?php if ($registrations && $registrations->num_rows > 0): ?>

                <?php while ($row = $registrations->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($row["registration_id"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["participant_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["email"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["phone"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["workshop"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["attendance_mode"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["number_of_seats"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["special_requirements"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["registration_date"]); ?>
                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="9">
                        No registrations found.
                    </td>

                </tr>

            <?php endif; ?>

        </tbody>

    </table>

    <h2>Workshop Registration Summary</h2>


    <table>

        <thead>

            <tr>

                <th>Workshop</th>

                <th>Total Registrations</th>

                <th>Total Seats Reserved</th>

            </tr>

        </thead>


        <tbody>

            <?php if ($workshop_totals && $workshop_totals->num_rows > 0): ?>

                <?php while ($row = $workshop_totals->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($row["workshop"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["total_registrations"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["total_seats"]); ?>
                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="3">
                        No workshop registrations available.
                    </td>

                </tr>

            <?php endif; ?>

        </tbody>

    </table>


</body>

</html>

<?php

$conn->close();

?>
