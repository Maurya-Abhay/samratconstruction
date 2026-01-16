<?php include __DIR__ . '/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rules and Regulations</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Use Inter font -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc; /* Light gray background */
        }
        .rule-list li {
            position: relative;
            padding-left: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .rule-list li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: #3b82f6; /* Blue marker */
            font-weight: 900;
        }
    </style>
</head>
<body class="min-h-screen">

    <div class="container mx-auto p-4 sm:p-8 lg:p-12 max-w-4xl">

        <!-- Header Section -->
        <header class="text-center mb-8">
            <div class="inline-flex items-center text-5xl font-extrabold text-gray-900 border-b-4 border-blue-500 pb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mr-3 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                Rules and Regulations
            </div>
            <p class="mt-2 text-xl text-gray-600">Essential guidelines for a productive and professional environment.</p>
        </header>
        
        <!-- Rules Card Container -->
        <div class="space-y-6">

            <!-- 1. Attendance Policy -->
            <div class="bg-white p-6 md:p-8 rounded-xl shadow-lg hover:shadow-xl transition duration-300 border-t-4 border-blue-500">
                <h3 class="text-2xl font-semibold text-blue-600 mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="M12 16h.01"/></svg>
                    Attendance Policy
                </h3>
                <ul class="rule-list text-gray-700">
                    <li>Mark your attendance daily as per the mode set by admin (Simple or Two-time).</li>
                    <li>Late marking or missing attendance may result in Absent status.</li>
                    <li>Follow entry and exit times strictly if Two-time mode is active.</li>
                </ul>
            </div>

            <!-- 2. General Conduct -->
            <div class="bg-white p-6 md:p-8 rounded-xl shadow-lg hover:shadow-xl transition duration-300 border-t-4 border-green-500">
                <h3 class="text-2xl font-semibold text-green-600 mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21a9 9 0 0 0 9-9c0-6-4-9-9-9s-9 3-9 9a9 9 0 0 0 9 9Z"/><path d="M10.84 8.76l4.24 4.24"/><path d="M10 14h.01"/><path d="M14 10h.01"/></svg>
                    General Conduct
                </h3>
                <ul class="rule-list text-gray-700">
                    <li>Maintain discipline and professionalism at the workplace.</li>
                    <li>Respect your colleagues and supervisors.</li>
                    <li>Use company property responsibly and report any damages immediately.</li>
                </ul>
            </div>

            <!-- 3. Leave Policy -->
            <div class="bg-white p-6 md:p-8 rounded-xl shadow-lg hover:shadow-xl transition duration-300 border-t-4 border-yellow-500">
                <h3 class="text-2xl font-semibold text-yellow-700 mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V6a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v14"/><path d="M3 14h3"/><path d="M21 14h-3"/><path d="M16 4l-4 4-4-4"/></svg>
                    Leave Policy
                </h3>
                <ul class="rule-list text-gray-700">
                    <li>Apply for leave in advance through the official portal.</li>
                    <li>Emergency leave must be informed to the immediate supervisor as soon as possible, followed by a formal application.</li>
                    <li>Ensure all pending tasks are handed over before commencing leave.</li>
                </ul>
            </div>

        </div>

    </div>

</body>
</html>
<?php include __DIR__ . '/footer.php'; ?>