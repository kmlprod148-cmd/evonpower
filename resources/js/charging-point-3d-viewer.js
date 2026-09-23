// 3D Model Viewer for Charging Points
class ChargingPoint3DViewer {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.model = null;
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.animationId = null;
        
        // Default configuration
        this.config = {
            modelPath: options.modelPath || '/models/charging-point.glb',
            autoRotate: options.autoRotate !== false,
            rotationSpeed: options.rotationSpeed || 0.005,
            enableShadows: options.enableShadows !== false,
            enableControls: options.enableControls !== false,
            showLoading: options.showLoading !== false,
            backgroundColor: options.backgroundColor || 0x000000,
            backgroundAlpha: options.backgroundAlpha || 0,
            ...options
        };
        
        this.init();
    }
    
    init() {
        if (!this.container) {
            console.error('Container not found:', this.containerId);
            return;
        }
        
        this.setupScene();
        this.setupCamera();
        this.setupRenderer();
        this.setupLighting();
        this.setupControls();
        this.loadModel();
        this.animate();
        this.setupResize();
    }
    
    setupScene() {
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(this.config.backgroundColor);
        this.scene.background.alpha = this.config.backgroundAlpha;
    }
    
    setupCamera() {
        const width = this.container.offsetWidth;
        const height = this.container.offsetHeight;
        
        this.camera = new THREE.PerspectiveCamera(75, width / height, 0.1, 1000);
        this.camera.position.set(0, 2, 5);
        this.camera.lookAt(0, 0, 0);
    }
    
    setupRenderer() {
        this.renderer = new THREE.WebGLRenderer({ 
            canvas: this.container, 
            alpha: true, 
            antialias: true 
        });
        
        this.renderer.setSize(this.container.offsetWidth, this.container.offsetHeight);
        this.renderer.setClearColor(0x000000, 0);
        this.renderer.shadowMap.enabled = this.config.enableShadows;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        this.renderer.outputEncoding = THREE.sRGBEncoding;
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1;
    }
    
    setupLighting() {
        // Ambient light for overall illumination
        const ambientLight = new THREE.AmbientLight(0x404040, 0.6);
        this.scene.add(ambientLight);
        
        // Main directional light
        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(10, 10, 5);
        directionalLight.castShadow = this.config.enableShadows;
        if (this.config.enableShadows) {
            directionalLight.shadow.mapSize.width = 2048;
            directionalLight.shadow.mapSize.height = 2048;
            directionalLight.shadow.camera.near = 0.5;
            directionalLight.shadow.camera.far = 50;
        }
        this.scene.add(directionalLight);
        
        // Fill light for better illumination
        const fillLight = new THREE.DirectionalLight(0xffffff, 0.3);
        fillLight.position.set(-10, 5, -5);
        this.scene.add(fillLight);
        
        // Rim light for better definition
        const rimLight = new THREE.DirectionalLight(0xffffff, 0.2);
        rimLight.position.set(0, 5, -10);
        this.scene.add(rimLight);
    }
    
    setupControls() {
        if (this.config.enableControls && typeof THREE.OrbitControls !== 'undefined') {
            this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
            this.controls.enableDamping = true;
            this.controls.dampingFactor = 0.05;
            this.controls.enableZoom = true;
            this.controls.enablePan = false;
            this.controls.maxPolarAngle = Math.PI / 2;
            this.controls.minDistance = 2;
            this.controls.maxDistance = 10;
        }
    }
    
    async loadModel() {
        if (this.config.showLoading) {
            this.showLoading();
        }
        
        try {
            const loader = new THREE.GLTFLoader();
            
            const gltf = await new Promise((resolve, reject) => {
                loader.load(
                    this.config.modelPath,
                    resolve,
                    (progress) => {
                        if (this.config.showLoading) {
                            this.updateProgress(progress);
                        }
                    },
                    reject
                );
            });
            
            this.model = gltf.scene;
            this.optimizeModel();
            this.centerAndScaleModel();
            this.scene.add(this.model);
            
            if (this.config.showLoading) {
                this.hideLoading();
            }
            
            console.log('✅ Charging point 3D model loaded successfully!');
            
        } catch (error) {
            console.error('❌ Error loading 3D model:', error);
            this.showError('Failed to load 3D model');
        }
    }
    
    optimizeModel() {
        if (!this.model) return;
        
        this.model.traverse((child) => {
            if (child.isMesh) {
                child.castShadow = this.config.enableShadows;
                child.receiveShadow = this.config.enableShadows;
                
                // Optimize materials
                if (child.material) {
                    if (Array.isArray(child.material)) {
                        child.material.forEach(material => this.optimizeMaterial(material));
                    } else {
                        this.optimizeMaterial(child.material);
                    }
                }
            }
        });
    }
    
    optimizeMaterial(material) {
        // Ensure proper encoding
        if (material.map) {
            material.map.encoding = THREE.sRGBEncoding;
        }
        if (material.normalMap) {
            material.normalMap.encoding = THREE.sRGBEncoding;
        }
        if (material.emissiveMap) {
            material.emissiveMap.encoding = THREE.sRGBEncoding;
        }
        
        // Optimize for performance
        material.needsUpdate = true;
    }
    
    centerAndScaleModel() {
        if (!this.model) return;
        
        const box = new THREE.Box3().setFromObject(this.model);
        const center = box.getCenter(new THREE.Vector3());
        const size = box.getSize(new THREE.Vector3());
        
        // Center the model
        this.model.position.sub(center);
        
        // Scale the model to fit nicely in the view
        const maxDim = Math.max(size.x, size.y, size.z);
        const scale = 3 / maxDim;
        this.model.scale.setScalar(scale);
        
        // Position the model slightly above ground
        this.model.position.y = -box.min.y * scale;
    }
    
    animate() {
        this.animationId = requestAnimationFrame(() => this.animate());
        
        if (this.config.autoRotate && this.model) {
            this.model.rotation.y += this.config.rotationSpeed;
        }
        
        if (this.controls) {
            this.controls.update();
        }
        
        this.renderer.render(this.scene, this.camera);
    }
    
    setupResize() {
        window.addEventListener('resize', () => {
            const width = this.container.offsetWidth;
            const height = this.container.offsetHeight;
            
            this.camera.aspect = width / height;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(width, height);
        });
    }
    
    showLoading() {
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'loading-3d';
        loadingDiv.className = 'absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg';
        loadingDiv.innerHTML = `
            <div class="text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto mb-2"></div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Loading 3D model...</p>
                <div id="progress-3d" class="text-xs text-gray-500 mt-1">0%</div>
            </div>
        `;
        this.container.style.position = 'relative';
        this.container.appendChild(loadingDiv);
    }
    
    updateProgress(progress) {
        const progressElement = document.getElementById('progress-3d');
        if (progressElement && progress.lengthComputable) {
            const percent = Math.round((progress.loaded / progress.total) * 100);
            progressElement.textContent = `${percent}%`;
        }
    }
    
    hideLoading() {
        const loadingDiv = document.getElementById('loading-3d');
        if (loadingDiv) {
            loadingDiv.remove();
        }
    }
    
    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'absolute inset-0 flex items-center justify-center bg-red-50 dark:bg-red-900/20 rounded-lg';
        errorDiv.innerHTML = `
            <div class="text-center text-red-600 dark:text-red-400">
                <svg class="mx-auto h-8 w-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm">${message}</p>
            </div>
        `;
        this.container.style.position = 'relative';
        this.container.appendChild(errorDiv);
    }
    
    // Public methods
    setRotationSpeed(speed) {
        this.config.rotationSpeed = speed;
    }
    
    toggleAutoRotate() {
        this.config.autoRotate = !this.config.autoRotate;
    }
    
    resetCamera() {
        if (this.controls) {
            this.controls.reset();
        } else {
            this.camera.position.set(0, 2, 5);
            this.camera.lookAt(0, 0, 0);
        }
    }
    
    destroy() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
        
        if (this.renderer) {
            this.renderer.dispose();
        }
        
        if (this.scene) {
            this.scene.clear();
        }
        
        window.removeEventListener('resize', this.setupResize);
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ChargingPoint3DViewer;
}
