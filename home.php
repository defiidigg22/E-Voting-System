<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Voting System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- tsParticles -->
    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2.12.0/tsparticles.bundle.min.js"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 50%, #f0abfc 100%);
            color: #22223b;
            overflow-x: hidden;
        }
        #tsparticles {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: 0;
        }
        header {
            background: none;
            padding: 0;
            margin: 0;
            text-align: center;
            z-index: 2;
            position: relative;
        }
        .evoting-title {
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: 2px;
            margin-top: 3.5rem;
            margin-bottom: 1.2rem;
            background: linear-gradient(90deg, #7c3aed, #6366f1, #f472b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            animation: animate__fadeInDown 1.2s;
        }
        .hero {
            min-height: 80vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 2;
            position: relative;
        }
        .hero-content {
            background: none;
            box-shadow: none;
            border-radius: 0;
            padding: 0 1rem;
            margin: 0 auto;
            max-width: 700px;
            width: 100%;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        .hero-content h1 {
            font-size: 2.7rem;
            font-weight: 700;
            background: linear-gradient(90deg, #a78bfa, #6366f1, #f472b6, #fbbf24);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            margin-bottom: 1.2rem;
            letter-spacing: 1px;
            animation: gradientMove 4s ease-in-out infinite, animate__fadeInDown 1.2s;
            text-shadow: 0 2px 6px #fff8, 0 1px 2px #6366f166;
            z-index: 2;
        }
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .hero-content p {
            font-size: 1.2rem;
            color: #22223b;
            margin-bottom: 2.2rem;
            animation: animate__fadeIn 1.5s;
            text-shadow: 0 1px 2px #fff4;
            z-index: 2;
        }
        .hero-content {
            position: relative;
        }
        .hero-blur-bg {
            position: absolute;
            top: -2rem; left: 50%;
            transform: translateX(-50%);
            width: 110%;
            height: 120%;
            background: rgba(40, 30, 80, 0.18);
            filter: blur(8px);
            border-radius: 2rem;
            z-index: 1;
        }
        .role-buttons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .role-button {
            background: linear-gradient(90deg, #a78bfa, #6366f1);
            padding: 16px 36px;
            border: none;
            border-radius: 2rem;
            font-size: 1.15rem;
            font-weight: 600;
            color: #fff;
            box-shadow: 0 4px 24px #6366f144;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.3s, box-shadow 0.3s, background 0.3s;
            position: relative;
            overflow: hidden;
            z-index: 2;
        }
        .role-button:before {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 100%;
            transform: translate(-50%, -50%);
            transition: width 0.4s cubic-bezier(0.23, 1, 0.32, 1), height 0.4s cubic-bezier(0.23, 1, 0.32, 1);
            z-index: 1;
        }
        .role-button:active:before {
            width: 200%;
            height: 500%;
        }
        .role-button:hover {
            transform: scale(1.08) rotate(-2deg);
            background: linear-gradient(90deg, #f472b6, #6366f1);
            box-shadow: 0 8px 32px #f472b655;
        }
        .footer {
            background: none;
            padding: 20px;
            margin-top: 20px;
            font-size: 1.1rem;
            letter-spacing: 1px;
            z-index: 2;
            position: relative;
            color: #6366f1;
        }
        @media (max-width: 600px) {
            .evoting-title { font-size: 2rem; margin-top: 2rem; }
            .hero-content h1 { font-size: 1.3rem; }
            .role-buttons { flex-direction: column; gap: 1rem; }
        }
    </style>
</head>
<body>
    <div id="tsparticles"></div>
    <header>
        <div class="evoting-title animate__animated animate__fadeInDown">E-Voting System</div>
    </header>
    <section class="hero">
        <div class="hero-content animate__animated animate__fadeInUp">
            <div class="hero-blur-bg"></div>
            <h1 class="animate__animated animate__fadeInDown">Secure, Transparent, and Fast E-Voting System</h1>
            <p class="animate__animated animate__fadeIn animate__delay-1s">Experience the next generation of digital voting with real-time security, transparency, and ease of use. Choose your role to get started!</p>
            <div class="role-buttons">
                <a href="index.php" class="role-button animate__animated animate__pulse animate__infinite" style="animation-delay:1.5s;">Login as Voter <i class="fas fa-user"></i></a>
                <a href="admin/admin_login.php" class="role-button animate__animated animate__pulse animate__infinite" style="animation-delay:2s;">Login as Admin <i class="fas fa-user-shield"></i></a>
            </div>
        </div>
    </section>
    <footer class="footer animate__animated animate__fadeInUp animate__delay-2s">
        &copy; 2025 E-Voting System. All rights reserved.
    </footer>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // tsParticles animated background
        tsParticles.load("tsparticles", {
            background: { color: { value: "#0000" } },
            fpsLimit: 60,
            particles: {
                number: { value: 70, density: { enable: true, area: 800 } },
                color: { value: ["#a78bfa", "#6366f1", "#f472b6"] },
                shape: { type: ["circle", "square"] },
                opacity: { value: 0.18 },
                size: { value: { min: 2, max: 7 } },
                move: { enable: true, speed: 1.3, direction: "none", outModes: { default: "out" } },
                links: { enable: true, distance: 120, color: "#6366f1", opacity: 0.13, width: 1 }
            },
            detectRetina: true
        });
        // Animate role buttons on click (ripple effect)
        document.querySelectorAll('.role-button').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const circle = document.createElement('span');
                circle.classList.add('ripple');
                const rect = btn.getBoundingClientRect();
                circle.style.width = circle.style.height = Math.max(rect.width, rect.height) + 'px';
                circle.style.left = (e.clientX - rect.left - rect.width/2) + 'px';
                circle.style.top = (e.clientY - rect.top - rect.height/2) + 'px';
                btn.appendChild(circle);
                setTimeout(() => circle.remove(), 600);
            });
        });
    </script>
</body>
</html>

