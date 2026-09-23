<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inscription - EvonPower</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/vendor/three.min.js') }}"></script>
    <style>
        body {
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            gap: 2rem;
        }
        .register-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }
        .model-container {
            width: 400px;
            height: 500px;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        #model3d {
            width: 100%;
            height: 100%;
            border-radius: 1rem;
        }
        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
                gap: 1rem;
            }
            .model-container {
                width: 100%;
                max-width: 400px;
                height: 300px;
            }
        }
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            margin-top: 0.5rem;
            font-size: 1rem;
        }
        .form-input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        .btn-primary {
            width: 100%;
            background: #10b981;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background: #059669;
        }
        .error {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        .link {
            color: #10b981;
            text-decoration: none;
        }
        .link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- 3D Model Container -->
        <div class="model-container">
            <canvas id="model3d"></canvas>
        </div>
        
        <!-- Register Form -->
        <div class="register-card">
            <!-- Logo & Header -->
            <div class="text-center mb-8">
                <img src="{{ asset('images/evon-logo.png') }}" alt="EvonPower" class="mx-auto h-16 w-auto mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Créer un compte</h1>
                <p class="text-gray-600">
                    ou <a href="{{ route('login') }}" class="link">connectez-vous</a>
                </p>
            </div>

            <!-- Register Form -->
            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- Name Field -->
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700">Nom complet</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        required
                        autocomplete="name"
                        autofocus
                        value="{{ old('name') }}"
                        class="form-input"
                        placeholder="Votre nom complet"
                    >
                    @error('name')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email Field -->
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700">Adresse e-mail</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        required
                        autocomplete="email"
                        value="{{ old('email') }}"
                        class="form-input"
                        placeholder="votre@email.com"
                    >
                    @error('email')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="form-input"
                        placeholder="••••••••"
                    >
                    @error('password')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password Field -->
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmer le mot de passe</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="form-input"
                        placeholder="••••••••"
                    >
                    @error('password_confirmation')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-primary">
                    Créer mon compte
                </button>
            </form>

            <!-- Footer Links -->
            <div class="text-center mt-8 pt-6 border-t border-gray-200">
                <div class="flex justify-center space-x-4 mb-4 text-sm">
                    <a href="{{ route('guest-reservations.index') }}" class="link">Mes Réservations</a>
                    <a href="#" class="link">Aide</a>
                    <a href="#" class="link">FAQ</a>
                </div>
                <p class="text-xs text-gray-500">&copy; {{ date('Y') }} EvonPower App. Tous droits réservés.</p>
            </div>
        </div>
    </div>

    <script>
        // Simple auto-focus
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('name');
            if (nameInput && !nameInput.value) {
                nameInput.focus();
            }
            
            // Initialize 3D Model
            init3DModel();
        });

        function init3DModel() {
            const canvas = document.getElementById('model3d');
            if (!canvas) return;

            // Scene setup
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, canvas.offsetWidth / canvas.offsetHeight, 0.1, 1000);
            const renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
            
            renderer.setSize(canvas.offsetWidth, canvas.offsetHeight);
            renderer.setClearColor(0x000000, 0);
            renderer.shadowMap.enabled = true;
            renderer.shadowMap.type = THREE.PCFSoftShadowMap;

            // Lighting
            const ambientLight = new THREE.AmbientLight(0x404040, 0.6);
            scene.add(ambientLight);

            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
            directionalLight.position.set(10, 10, 5);
            directionalLight.castShadow = true;
            scene.add(directionalLight);

            // Create EV charging station model
            createChargingStation(scene);

            // Camera position
            camera.position.set(0, 2, 5);
            camera.lookAt(0, 0, 0);

            // Animation loop
            function animate() {
                requestAnimationFrame(animate);
                
                // Rotate the entire scene
                scene.rotation.y += 0.005;
                
                renderer.render(scene, camera);
            }
            animate();

            // Handle resize
            window.addEventListener('resize', () => {
                const width = canvas.offsetWidth;
                const height = canvas.offsetHeight;
                camera.aspect = width / height;
                camera.updateProjectionMatrix();
                renderer.setSize(width, height);
            });
        }

        function createChargingStation(scene) {
            // Base platform
            const baseGeometry = new THREE.CylinderGeometry(2, 2, 0.2, 32);
            const baseMaterial = new THREE.MeshLambertMaterial({ color: 0x2d3748 });
            const base = new THREE.Mesh(baseGeometry, baseMaterial);
            base.position.y = -0.1;
            base.receiveShadow = true;
            scene.add(base);

            // Main column
            const columnGeometry = new THREE.CylinderGeometry(0.3, 0.4, 3, 16);
            const columnMaterial = new THREE.MeshLambertMaterial({ color: 0x10b981 });
            const column = new THREE.Mesh(columnGeometry, columnMaterial);
            column.position.y = 1.4;
            column.castShadow = true;
            scene.add(column);

            // Charging head
            const headGeometry = new THREE.BoxGeometry(0.8, 0.6, 0.4);
            const headMaterial = new THREE.MeshLambertMaterial({ color: 0x1f2937 });
            const head = new THREE.Mesh(headGeometry, headMaterial);
            head.position.set(0.6, 2.8, 0);
            head.castShadow = true;
            scene.add(head);

            // Cable
            const cableGeometry = new THREE.CylinderGeometry(0.02, 0.02, 2, 8);
            const cableMaterial = new THREE.MeshLambertMaterial({ color: 0x374151 });
            const cable = new THREE.Mesh(cableGeometry, cableMaterial);
            cable.position.set(0.6, 1.5, 0);
            cable.rotation.z = Math.PI / 4;
            cable.castShadow = true;
            scene.add(cable);

            // Charging connector
            const connectorGeometry = new THREE.SphereGeometry(0.1, 16, 16);
            const connectorMaterial = new THREE.MeshLambertMaterial({ color: 0xef4444 });
            const connector = new THREE.Mesh(connectorGeometry, connectorMaterial);
            connector.position.set(1.2, 2.2, 0);
            connector.castShadow = true;
            scene.add(connector);

            // Status LED
            const ledGeometry = new THREE.SphereGeometry(0.05, 16, 16);
            const ledMaterial = new THREE.MeshLambertMaterial({ color: 0x22c55e });
            const led = new THREE.Mesh(ledGeometry, ledMaterial);
            led.position.set(0.6, 3.1, 0.2);
            scene.add(led);

            // Add pulsing effect to LED
            function pulseLED() {
                led.material.color.setHex(led.material.color.getHex() === 0x22c55e ? 0x16a34a : 0x22c55e);
                setTimeout(pulseLED, 1000);
            }
            pulseLED();

            // Floating particles
            for (let i = 0; i < 20; i++) {
                const particleGeometry = new THREE.SphereGeometry(0.02, 8, 8);
                const particleMaterial = new THREE.MeshLambertMaterial({ 
                    color: 0x60a5fa,
                    transparent: true,
                    opacity: 0.6
                });
                const particle = new THREE.Mesh(particleGeometry, particleMaterial);
                
                particle.position.set(
                    (Math.random() - 0.5) * 8,
                    Math.random() * 4 + 1,
                    (Math.random() - 0.5) * 8
                );
                
                particle.userData = {
                    velocity: {
                        x: (Math.random() - 0.5) * 0.02,
                        y: Math.random() * 0.01 + 0.005,
                        z: (Math.random() - 0.5) * 0.02
                    }
                };
                
                scene.add(particle);
            }

            // Animate particles
            function animateParticles() {
                scene.children.forEach(child => {
                    if (child.userData.velocity) {
                        child.position.x += child.userData.velocity.x;
                        child.position.y += child.userData.velocity.y;
                        child.position.z += child.userData.velocity.z;
                        
                        // Reset position if too far
                        if (child.position.y > 5) {
                            child.position.y = 1;
                        }
                        if (Math.abs(child.position.x) > 4) {
                            child.position.x = (Math.random() - 0.5) * 8;
                        }
                        if (Math.abs(child.position.z) > 4) {
                            child.position.z = (Math.random() - 0.5) * 8;
                        }
                    }
                });
                requestAnimationFrame(animateParticles);
            }
            animateParticles();
        }
    </script>
</body>
</html>
