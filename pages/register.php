<?php
include('../includes/header.php');
include('../includes/db_connection.php');

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    if ($role == 'victim') {
        // Process victim registration
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        
        // Insert into users table
        $sql = "INSERT INTO users (username, email, password, full_name, role) 
                VALUES ('$username', '$email', '$password', '$full_name', 'user')";
                
        if ($conn->query($sql) === TRUE) {
            $success = "Registration successful! You can now login.";
        } else {
            $error = "Error: " . $sql . "<br>" . $conn->error;
        }
    } 
    elseif ($role == 'agent') {
        // Process insurance agent registration
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $region = mysqli_real_escape_string($conn, $_POST['region']);
        $corporation = mysqli_real_escape_string($conn, $_POST['corporation']);
        
        // File upload handling
        $document_path = '';
        if(isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
            $upload_dir = '../uploads/documents/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = time() . '_' . basename($_FILES['document']['name']);
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['document']['tmp_name'], $target_file)) {
                $document_path = $target_file;
            } else {
                $error = "Error uploading your document.";
            }
        } else {
            $error = "Document upload is required.";
        }
        
        if (empty($error)) {
            // Insert into insurance_agent table
            $status = 'pending'; // Default status for new agents
            
            $sql = "INSERT INTO insurance_agent (username, email, password, full_name, region, corporation, document_path, status) 
                    VALUES ('$username', '$email', '$password', '$full_name', '$region', '$corporation', '$document_path', '$status')";
                    
            if ($conn->query($sql) === TRUE) {
                // Send email to admin for review (you'll need to implement this)
                // notifyAdmin($email, $full_name, $corporation);
                
                $success = "Your registration has been submitted for review. You will be notified via email once approved.";
            } else {
                $error = "Error: " . $sql . "<br>" . $conn->error;
            }
        }
    }
}

// Get list of Indian states for the dropdown
$states = [
    "Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar", "Chhattisgarh", 
    "Goa", "Gujarat", "Haryana", "Himachal Pradesh", "Jharkhand", 
    "Karnataka", "Kerala", "Madhya Pradesh", "Maharashtra", "Manipur", 
    "Meghalaya", "Mizoram", "Nagaland", "Odisha", "Punjab", 
    "Rajasthan", "Sikkim", "Tamil Nadu", "Telangana", "Tripura", 
    "Uttar Pradesh", "Uttarakhand", "West Bengal",
    "Andaman and Nicobar Islands", "Chandigarh", "Dadra and Nagar Haveli and Daman and Diu",
    "Delhi", "Jammu and Kashmir", "Ladakh", "Lakshadweep", "Puducherry"
];

// Get insurance companies from database
$insurance_companies = [];
$sql = "SELECT id, name FROM insurance_companies ORDER BY name";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $insurance_companies[$row['id']] = $row['name'];
    }
}
?>

<div class="flex min-h-screen bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md mx-auto">
            <h1 class="text-2xl font-bold mb-4">Register</h1>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success; ?>
                    <?php if (strpos($success, "You can now login") !== false): ?>
                        <p><a href="login.php" class="text-blue-500 underline">Login here</a></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Initial role selection form -->
                <div id="role-selection-form" class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        I am registering as:
                    </label>
                    <div class="flex space-x-4 mb-4">
                        <button type="button" id="victim-btn" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline flex-1">
                            Accident Victim
                        </button>
                        <button type="button" id="agent-btn"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline flex-1">
                            Insurance Agent
                        </button>
                    </div>
                </div>

                <!-- Victim registration form (initially hidden) -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="victim-form" class="hidden" enctype="multipart/form-data">
                    <input type="hidden" name="role" value="victim">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="full_name">
                            Full Name
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="full_name" name="full_name" type="text" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                            Email
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="email" name="email" type="email" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                            Username
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="username" name="username" type="text" required>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                            Password
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="password" name="password" type="password" required>
                    </div>

                    <div class="flex items-center justify-between">
                        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            type="submit">
                            Register
                        </button>
                        <a class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800" href="login.php">
                            Already have an account?
                        </a>
                    </div>
                </form>

                <!-- Agent registration form (initially hidden) -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="agent-form" class="hidden" enctype="multipart/form-data">
                    <input type="hidden" name="role" value="agent">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="agent_full_name">
                            Full Name
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="agent_full_name" name="full_name" type="text" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="agent_email">
                            Email
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="agent_email" name="email" type="email" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="region">
                            Region
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="region" name="region" required>
                            <option value="">Select a state</option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?php echo $state; ?>"><?php echo $state; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="corporation">
                            Insurance Company
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="corporation" name="corporation" required>
                            <option value="">Select an insurance company</option>
                            <?php foreach ($insurance_companies as $id => $name): ?>
                                <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="document">
                            ID Document
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="document" name="document" type="file" accept="image/*,.pdf" required>
                        <p class="text-sm text-gray-500 mt-1">Please upload a valid ID proof (image or PDF).</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="agent_username">
                            Username
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="agent_username" name="username" type="text" required>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="agent_password">
                            Password
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="agent_password" name="password" type="password" required>
                    </div>

                    <div class="flex items-center justify-between">
                        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            type="submit">
                            Submit for Review
                        </button>
                        <a class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800" href="login.php">
                            Already have an account?
                        </a>
                    </div>
                </form>

                <script>
                    // Show the appropriate form based on role selection
                    document.getElementById('victim-btn').addEventListener('click', function() {
                        document.getElementById('role-selection-form').classList.add('hidden');
                        document.getElementById('victim-form').classList.remove('hidden');
                    });
                    
                    document.getElementById('agent-btn').addEventListener('click', function() {
                        document.getElementById('role-selection-form').classList.add('hidden');
                        document.getElementById('agent-form').classList.remove('hidden');
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>