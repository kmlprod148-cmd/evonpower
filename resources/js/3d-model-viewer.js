// 3D Model Viewer Component for hitem3d.glb
class Model3DViewer {
    constructor(containerId, modelPath) {
        this.containerId = containerId;
        this.modelPath = modelPath;
        this.container = null;
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.model = null;
        this.animationId = null;
        this.loadingTimeout = null;
        this.progressCallback = null;
        this.retryCount = 0;
        
        // Utiliser la configuration de performance
        this.config = window.Model3DConfig || {
            performance: { enableShadows: false, shadowMapSize: 512, enableAntialiasing: false, maxPixelRatio: 2 },
            loading: { timeout: 8000, showProgress: true, retryCount: 2 },
            animation: { rotationSpeed: 0.005, floatAmplitude: 0.1, floatSpeed: 0.002 },
            lighting: { ambientIntensity: 0.8, directionalIntensity: 0.6 }
        };
        
        this.rotationSpeed = this.config.animation.rotationSpeed;
        this.floatAmplitude = this.config.animation.floatAmplitude;
        this.floatSpeed = this.config.animation.floatSpeed;
        this.time = 0;
        
        this.init();
    }
    
    async init() {
        try {
            // Set loading timeout from config
            this.loadingTimeout = setTimeout(() => {
                console.warn('Timeout de chargement du modèle 3D - Affichage du fallback');
                this.showFallback();
            }, this.config.loading.timeout);
            
            // Check if Three.js is available
            if (typeof THREE === 'undefined') {
                await this.loadThreeJS();
            }
            
            this.setupScene();
            this.setupCamera();
            this.setupRenderer();
            this.setupLighting();
            await this.loadModel();
            
            // Clear timeout on success
            if (this.loadingTimeout) {
                clearTimeout(this.loadingTimeout);
                this.loadingTimeout = null;
            }
            
            this.animate();
            this.handleResize();
        } catch (error) {
            console.error('Erreur lors de l\'initialisation du modèle 3D:', error);
            
            // Retry logic
            if (this.retryCount < this.config.loading.retryCount) {
                this.retryCount++;
                console.log(`Tentative de retry ${this.retryCount}/${this.config.loading.retryCount}`);
                setTimeout(() => this.init(), this.config.loading.retryDelay);
                return;
            }
            
            this.showFallback();
        }
    }
    
    async loadThreeJS() {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js';
            script.onload = () => {
                // Load GLTFLoader
                const gltfScript = document.createElement('script');
                gltfScript.src = 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js';
                gltfScript.onload = resolve;
                gltfScript.onerror = reject;
                document.head.appendChild(gltfScript);
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    setupScene() {
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x000000);
        
        // Add fog only if enabled in config
        if (this.config.performance.enableFog) {
            this.scene.fog = new THREE.Fog(0x000000, 1, 10);
        }
    }
    
    setupCamera() {
        this.camera = new THREE.PerspectiveCamera(
            75,
            window.innerWidth / window.innerHeight,
            0.1,
            1000
        );
        this.camera.position.set(0, 0, 3);
    }
    
    setupRenderer() {
        this.container = document.getElementById(this.containerId);
        if (!this.container) {
            throw new Error(`Container ${this.containerId} non trouvé`);
        }
        
        this.renderer = new THREE.WebGLRenderer({ 
            antialias: this.config.performance.enableAntialiasing,
            alpha: true 
        });
        this.renderer.setSize(this.container.clientWidth, this.container.clientHeight);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, this.config.performance.maxPixelRatio));
        this.renderer.shadowMap.enabled = this.config.performance.enableShadows;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        this.renderer.outputEncoding = THREE.sRGBEncoding;
        
        // Set shadow map size from config
        if (this.config.performance.enableShadows) {
            this.renderer.shadowMap.mapSize.width = this.config.performance.shadowMapSize;
            this.renderer.shadowMap.mapSize.height = this.config.performance.shadowMapSize;
        }
        
        this.container.appendChild(this.renderer.domElement);
    }
    
    setupLighting() {
        // Use config values for lighting
        const ambientLight = new THREE.AmbientLight(
            this.config.lighting.ambientColor, 
            this.config.lighting.ambientIntensity
        );
        this.scene.add(ambientLight);
        
        const directionalLight = new THREE.DirectionalLight(
            this.config.lighting.directionalColor, 
            this.config.lighting.directionalIntensity
        );
        directionalLight.position.set(5, 5, 5);
        directionalLight.castShadow = this.config.performance.enableShadows;
        
        if (this.config.performance.enableShadows) {
            directionalLight.shadow.mapSize.width = this.config.performance.shadowMapSize;
            directionalLight.shadow.mapSize.height = this.config.performance.shadowMapSize;
        }
        
        this.scene.add(directionalLight);
    }
    
    async loadModel() {
        return new Promise((resolve, reject) => {
            const loader = new THREE.GLTFLoader();
            
            // Update progress callback only if enabled
            if (this.config.loading.showProgress) {
                this.progressCallback = (progress) => {
                    const percent = Math.round((progress.loaded / progress.total) * 100);
                    console.log(`Chargement du modèle: ${percent}%`);
                    
                    // Update loading indicator
                    const loadingElement = document.getElementById('3d-loading');
                    if (loadingElement) {
                        const progressText = loadingElement.querySelector('p');
                        if (progressText) {
                            progressText.textContent = `Chargement du modèle 3D... ${percent}%`;
                        }
                    }
                };
            }
            
            loader.load(
                this.modelPath,
                (gltf) => {
                    this.model = gltf.scene;
                    
                    // Center the model
                    const box = new THREE.Box3().setFromObject(this.model);
                    const center = box.getCenter(new THREE.Vector3());
                    this.model.position.sub(center);
                    
                    // Scale the model appropriately
                    const size = box.getSize(new THREE.Vector3());
                    const maxDim = Math.max(size.x, size.y, size.z);
                    const scale = 2 / maxDim;
                    this.model.scale.setScalar(scale);
                    
                    // Apply material optimizations from config
                    this.model.traverse((child) => {
                        if (child.isMesh) {
                            child.castShadow = this.config.performance.enableShadows;
                            child.receiveShadow = this.config.performance.enableShadows;
                            
                            // Simplify materials if enabled
                            if (this.config.performance.simplifyMaterials && child.material) {
                                child.material.metalness = 0.2;
                                child.material.roughness = 0.8;
                            }
                        }
                    });
                    
                    this.scene.add(this.model);
                    resolve();
                },
                this.progressCallback,
                (error) => {
                    console.error('Erreur lors du chargement du modèle:', error);
                    reject(error);
                }
            );
        });
    }
    
    animate() {
        this.animationId = requestAnimationFrame(() => this.animate());
        
        if (this.model && this.config.animation.enabled) {
            // Rotation
            this.model.rotation.y += this.rotationSpeed;
            
            // Floating animation
            this.time += this.floatSpeed;
            this.model.position.y = Math.sin(this.time) * this.floatAmplitude;
            
            // Gentle sway
            this.model.rotation.z = Math.sin(this.time * 0.5) * 0.05;
        }
        
        this.renderer.render(this.scene, this.camera);
    }
    
    handleResize() {
        window.addEventListener('resize', () => {
            if (this.container && this.camera && this.renderer) {
                const width = this.container.clientWidth;
                const height = this.container.clientHeight;
                
                this.camera.aspect = width / height;
                this.camera.updateProjectionMatrix();
                this.renderer.setSize(width, height);
            }
        });
    }
    
    showFallback() {
        const container = document.getElementById(this.containerId);
        if (container) {
            const message = this.config.fallback?.message || "Modèle 3D EvonPower";
            const subMessage = this.config.fallback?.subMessage || "Chargement optimisé";
            
            container.innerHTML = `
                <div class="flex items-center justify-center h-full bg-gradient-to-br from-gray-900 to-gray-800 rounded-lg">
                    <div class="text-center">
                        <svg class="mx-auto h-16 w-16 text-green-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-9 0h10m-10 0a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2" />
                        </svg>
                        <p class="text-green-400 font-medium">${message}</p>
                        <p class="text-gray-400 text-sm mt-2">${subMessage}</p>
                    </div>
                </div>
            `;
        }
    }
    
    destroy() {
        if (this.loadingTimeout) {
            clearTimeout(this.loadingTimeout);
        }
        
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
        
        if (this.renderer && this.container) {
            this.container.removeChild(this.renderer.domElement);
            this.renderer.dispose();
        }
        
        if (this.scene) {
            this.scene.clear();
        }
    }
}

// Export for use in other files
window.Model3DViewer = Model3DViewer;
