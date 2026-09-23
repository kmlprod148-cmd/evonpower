// 3D Model Viewer Optimisé pour EvonPower
// Centrage parfait et optimisations de performance
class OptimizedModel3DViewer {
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
        
        // Configuration optimisée pour la performance
        this.config = {
            performance: { 
                enableShadows: false, 
                shadowMapSize: 512, 
                enableAntialiasing: false, 
                maxPixelRatio: 1.5,
                enableFog: false,
                simplifyMaterials: true
            },
            loading: { 
                timeout: 10000, 
                showProgress: true, 
                retryCount: 2,
                retryDelay: 2000
            },
            animation: { 
                enabled: true,
                rotationSpeed: 0.003, 
                floatAmplitude: 0.08, 
                floatSpeed: 0.0015,
                swayIntensity: 0.03
            },
            lighting: { 
                ambientIntensity: 0.7, 
                directionalIntensity: 0.5,
                enableHemisphere: true
            },
            camera: {
                fov: 45,
                near: 0.1,
                far: 1000,
                position: { x: 0, y: 2, z: 5 }
            }
        };
        
        this.rotationSpeed = this.config.animation.rotationSpeed;
        this.floatAmplitude = this.config.animation.floatAmplitude;
        this.floatSpeed = this.config.animation.floatSpeed;
        this.swayIntensity = this.config.animation.swayIntensity;
        this.time = 0;
        
        this.init();
    }
    
    async init() {
        try {
            console.log('🚀 Initialisation du viewer 3D optimisé...');
            
            // Timeout de chargement
            this.loadingTimeout = setTimeout(() => {
                console.warn('⏰ Timeout de chargement - Affichage du fallback');
                this.showFallback();
            }, this.config.loading.timeout);
            
            // Vérifier Three.js
            if (typeof THREE === 'undefined') {
                await this.loadThreeJS();
            }
            
            this.setupScene();
            this.setupCamera();
            this.setupRenderer();
            this.setupLighting();
            await this.loadModel();
            
            // Nettoyer le timeout
            if (this.loadingTimeout) {
                clearTimeout(this.loadingTimeout);
                this.loadingTimeout = null;
            }
            
            this.animate();
            this.handleResize();
            this.updateLoadingState('loaded');
            
            console.log('✅ Viewer 3D initialisé avec succès');
            
        } catch (error) {
            console.error('❌ Erreur lors de l\'initialisation:', error);
            
            // Logique de retry
            if (this.retryCount < this.config.loading.retryCount) {
                this.retryCount++;
                console.log(`🔄 Tentative de retry ${this.retryCount}/${this.config.loading.retryCount}`);
                setTimeout(() => this.init(), this.config.loading.retryDelay);
                return;
            }
            
            this.showFallback();
        }
    }
    
    async loadThreeJS() {
        return new Promise((resolve, reject) => {
            console.log('📦 Chargement de Three.js...');
            
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js';
            script.onload = () => {
                // Charger GLTFLoader
                const gltfScript = document.createElement('script');
                gltfScript.src = 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js';
                gltfScript.onload = () => {
                    console.log('✅ Three.js et GLTFLoader chargés');
                    resolve();
                };
                gltfScript.onerror = reject;
                document.head.appendChild(gltfScript);
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    setupScene() {
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x0f172a);
        
        // Ajouter du brouillard si activé
        if (this.config.performance.enableFog) {
            this.scene.fog = new THREE.Fog(0x0f172a, 1, 10);
        }
    }
    
    setupCamera() {
        const container = document.getElementById(this.containerId);
        if (!container) return;
        
        const aspect = container.clientWidth / container.clientHeight;
        this.camera = new THREE.PerspectiveCamera(
            this.config.camera.fov,
            aspect,
            this.config.camera.near,
            this.config.camera.far
        );
        
        // Position de la caméra optimisée
        this.camera.position.set(
            this.config.camera.position.x,
            this.config.camera.position.y,
            this.config.camera.position.z
        );
        
        this.camera.lookAt(0, 0, 0);
    }
    
    setupRenderer() {
        const container = document.getElementById(this.containerId);
        if (!container) return;
        
        this.renderer = new THREE.WebGLRenderer({
            antialias: this.config.performance.enableAntialiasing,
            alpha: false,
            powerPreference: 'high-performance'
        });
        
        this.renderer.setSize(container.clientWidth, container.clientHeight);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, this.config.performance.maxPixelRatio));
        this.renderer.shadowMap.enabled = this.config.performance.enableShadows;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        
        // Optimisations de performance
        this.renderer.outputEncoding = THREE.sRGBEncoding;
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1.0;
        
        // Ajouter le canvas au conteneur
        container.appendChild(this.renderer.domElement);
        this.renderer.domElement.id = '3d-canvas';
        this.renderer.domElement.style.display = 'block';
    }
    
    setupLighting() {
        // Lumière ambiante
        const ambientLight = new THREE.AmbientLight(
            0xffffff, 
            this.config.lighting.ambientIntensity
        );
        this.scene.add(ambientLight);
        
        // Lumière directionnelle principale
        const directionalLight = new THREE.DirectionalLight(
            0xffffff, 
            this.config.lighting.directionalIntensity
        );
        directionalLight.position.set(5, 5, 5);
        directionalLight.castShadow = this.config.performance.enableShadows;
        
        if (this.config.performance.enableShadows) {
            directionalLight.shadow.mapSize.width = this.config.performance.shadowMapSize;
            directionalLight.shadow.mapSize.height = this.config.performance.shadowMapSize;
        }
        
        this.scene.add(directionalLight);
        
        // Lumière hémisphérique pour un éclairage plus naturel
        if (this.config.lighting.enableHemisphere) {
            const hemisphereLight = new THREE.HemisphereLight(0x87ceeb, 0x1a1a1a, 0.3);
            this.scene.add(hemisphereLight);
        }
    }
    
    async loadModel() {
        return new Promise((resolve, reject) => {
            console.log('📥 Chargement du modèle 3D...');
            
            const loader = new THREE.GLTFLoader();
            
            // Callback de progression
            if (this.config.loading.showProgress) {
                this.progressCallback = (progress) => {
                    const percent = Math.round((progress.loaded / progress.total) * 100);
                    console.log(`📊 Progression: ${percent}%`);
                    this.updateProgress(percent);
                };
            }
            
            loader.load(
                this.modelPath,
                (gltf) => {
                    console.log('✅ Modèle 3D chargé avec succès');
                    
                    this.model = gltf.scene;
                    
                    // Centrage parfait du modèle
                    this.centerModel();
                    
                    // Optimisations des matériaux
                    this.optimizeMaterials();
                    
                    this.scene.add(this.model);
                    resolve();
                },
                this.progressCallback,
                (error) => {
                    console.error('❌ Erreur lors du chargement:', error);
                    reject(error);
                }
            );
        });
    }
    
    centerModel() {
        if (!this.model) return;
        
        // Calculer la boîte englobante
        const box = new THREE.Box3().setFromObject(this.model);
        const center = box.getCenter(new THREE.Vector3());
        const size = box.getSize(new THREE.Vector3());
        
        // Centrer le modèle
        this.model.position.sub(center);
        
        // Mise à l'échelle optimale
        const maxDim = Math.max(size.x, size.y, size.z);
        const scale = 1.8 / maxDim; // Légèrement plus petit pour l'espace
        this.model.scale.setScalar(scale);
        
        // Ajuster la position de la caméra pour cadrer parfaitement
        const distance = maxDim * 2.5;
        this.camera.position.set(0, distance * 0.3, distance);
        this.camera.lookAt(0, 0, 0);
        this.camera.updateProjectionMatrix();
        
        console.log('🎯 Modèle centré et mis à l\'échelle');
    }
    
    optimizeMaterials() {
        if (!this.model) return;
        
        this.model.traverse((child) => {
            if (child.isMesh) {
                // Optimisations des ombres
                child.castShadow = this.config.performance.enableShadows;
                child.receiveShadow = this.config.performance.enableShadows;
                
                // Simplification des matériaux si activée
                if (this.config.performance.simplifyMaterials && child.material) {
                    if (child.material.metalness !== undefined) {
                        child.material.metalness = 0.3;
                        child.material.roughness = 0.7;
                    }
                }
                
                // Optimisation de la géométrie
                if (child.geometry) {
                    child.geometry.computeBoundingSphere();
                    child.geometry.computeBoundingBox();
                }
            }
        });
        
        console.log('🔧 Matériaux optimisés');
    }
    
    animate() {
        this.animationId = requestAnimationFrame(() => this.animate());
        
        if (this.model && this.config.animation.enabled) {
            // Rotation fluide
            this.model.rotation.y += this.rotationSpeed;
            
            // Animation de flottement
            this.time += this.floatSpeed;
            this.model.position.y = Math.sin(this.time) * this.floatAmplitude;
            
            // Balancement doux
            this.model.rotation.z = Math.sin(this.time * 0.5) * this.swayIntensity;
        }
        
        if (this.renderer && this.scene && this.camera) {
            this.renderer.render(this.scene, this.camera);
        }
    }
    
    handleResize() {
        window.addEventListener('resize', () => {
            if (this.container && this.camera && this.renderer) {
                const container = document.getElementById(this.containerId);
                if (!container) return;
                
                const width = container.clientWidth;
                const height = container.clientHeight;
                
                this.camera.aspect = width / height;
                this.camera.updateProjectionMatrix();
                this.renderer.setSize(width, height);
                
                console.log('📱 Redimensionnement géré:', width, 'x', height);
            }
        });
    }
    
    updateProgress(percent) {
        const loadingElement = document.getElementById('modelLoading');
        if (loadingElement) {
            const progressText = loadingElement.querySelector('p');
            if (progressText) {
                progressText.textContent = `Chargement du modèle 3D... ${percent}%`;
            }
        }
    }
    
    updateLoadingState(state) {
        const container = document.getElementById(this.containerId);
        if (container) {
            container.className = `model-container ${state}`;
        }
        
        // Masquer l'écran de chargement
        if (state === 'loaded') {
            const loadingElement = document.getElementById('modelLoading');
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
        }
    }
    
    showFallback() {
        console.log('⚠️ Affichage du fallback');
        const container = document.getElementById(this.containerId);
        if (container) {
            container.innerHTML = `
                <div class="model-loading">
                    <div class="battery-icon">🔋</div>
                    <h3 class="model-title">EvonPower</h3>
                    <p class="model-description">Modèle 3D non disponible</p>
                    <p style="font-size: 0.75rem; opacity: 0.6;">Veuillez réessayer plus tard</p>
                </div>
            `;
        }
    }
    
    destroy() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
        
        if (this.loadingTimeout) {
            clearTimeout(this.loadingTimeout);
        }
        
        if (this.renderer) {
            this.renderer.dispose();
        }
        
        if (this.container && this.renderer) {
            this.container.removeChild(this.renderer.domElement);
        }
        
        console.log('🗑️ Viewer 3D détruit');
    }
}

// Export global
window.OptimizedModel3DViewer = OptimizedModel3DViewer;
