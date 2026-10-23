
	import * as THREE from '/themes/Acme/public/assets/js/threejs/three.module.js';

	import Stats from '/themes/Acme/public/assets/js/threejs/addons/libs/stats.module.js';
	
    import { GUI } from '/themes/Acme/public/assets/js/threejs/addons/libs/lil-gui.module.min.js';
	import { OrbitControls } from '/themes/Acme/public/assets/js/threejs/addons/controls/OrbitControls.js';
	import { RoomEnvironment } from '/themes/Acme/public/assets/js/threejs/addons/environments/RoomEnvironment.js';

	import { GLTFLoader } from '/themes/Acme/public/assets/js/threejs/addons/loaders/GLTFLoader.js';
	import { DRACOLoader } from '/themes/Acme/public/assets/js/threejs/addons/loaders/DRACOLoader.js';
	

	let mixer,gltfs,loopId;
	let scene,camera,renderer;
    let effectController;
    let bline;


	
	


/*	window.onresize = function () {

		camera.aspect = window.innerWidth / window.innerHeight;
		camera.updateProjectionMatrix();

		renderer.setSize( window.innerWidth, window.innerHeight );

	};*/


	function init3d(glbUrl,container,onCompleteCallback) {
	    console.log("init3d")

       // const container = document.getElementById( 'img_container' );

/*		const stats = new Stats();
		container.appendChild( stats.dom );*/


        // 创建渲染器
	//	renderer = new THREE.WebGLRenderer( { antialias: true } );
	    // 创建渲染器，并设置alpha为true以支持透明背景
		renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        // 将渲染器的像素比例（pixel ratio）设置为与当前设备的物理像素比率（device pixel ratio）相匹配。这是为了确保渲染的内容在高DPI（像素密度）屏幕上呈现得更清晰，而不会出现模糊的情况。
        // window.devicePixelRatio返回当前设备的像素比率，通常是1或更高。如果设备的像素比率为1，表示标准DPI屏幕，而如果像素比率高于1，表示高DPI或Retina屏幕，其显示的像素密度更高。
		renderer.setPixelRatio( window.devicePixelRatio );
		renderer.setSize(800, 800);
        // 设置了Three.js渲染器（renderer）的色调映射（Tone Mapping）方式为 THREE.ACESFilmicToneMapping。
        	// THREE.ACESFilmicToneMapping 是一种现代色调映射算法，旨在提供更真实的颜色和光照效果，以模拟电影摄影中的感觉。这种算法通常用于渲染引擎中，以确保渲染的图像在不同光照条件下看起来更自然和逼真。
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        	// 设置了Three.js渲染器（renderer）的曝光值（Exposure）。曝光值是用于控制图像的亮度的参数，它影响了图像的整体明亮度。
        	// renderer.toneMappingExposure 设置为1，表示没有明显的曝光修正。当曝光值为1时，图像的亮度不会发生明显的变化，即图像保持原始的亮度。如果曝光值小于1，图像会变得较暗；如果曝光值大于1，图像会变得较亮。
        renderer.toneMappingExposure = 1;
        	//  是否启用旧版的光照系统
        	// 当 useLegacyLights 设置为 false 时，表示使用现代的光照系统，而不使用旧版的光照系统。这意味着Three.js将采用更现代、更高质量的光照和阴影计算方法，以获得更逼真的渲染效果。
        renderer.useLegacyLights = false;

		container.appendChild( renderer.domElement );



		scene = new THREE.Scene();
    	// 创建RoomEnvironment 对象，用于创建一种模拟室内环境的光照和背景效果。主要目的是增强3D场景的视觉效果，使渲染的内容看起来更加逼真。
    	const environment = new RoomEnvironment(renderer);
    	// 创建了 PMREMGenerator 对象，用于预计算环境贴图，以提高渲染效果的质量和性能。
    	const pmremGenerator = new THREE.PMREMGenerator(renderer);
    	//  设置背景颜色透明
    //	scene.background = new THREE.Color( 0xbfe3dd );
    	scene.background = null;
    	// 以模拟更逼真的光照和反射效果。这有助于提高渲染场景的视觉质量，特别是在处理材质的反射效果时。
    	scene.environment = pmremGenerator.fromScene(environment).texture;

        const containerWidth = container.clientWidth;  // 获取container的宽度
        const containerHeight = container.clientHeight; // 获取container的高度
        
        camera = new THREE.PerspectiveCamera(40, containerWidth / containerHeight, 1, 100);

		camera.position.set( 5, 2, 8 );
		

		

		const controls = new OrbitControls( camera, renderer.domElement );
		controls.target.set( 0, 0.5, 0 );
		controls.update();
		//controls.enablePan = false;
        controls.minDistance = 1; // 最小距离
        controls.maxDistance = 20; // 最大距离
		controls.enableDamping = true;

		const dracoLoader = new DRACOLoader();
		dracoLoader.setDecoderPath( '/themes/Acme/threed/jsm/libs/draco/gltf/' );

		const loader = new GLTFLoader();
		loader.setDRACOLoader( dracoLoader );
	    console.log(glbUrl)
	    loader.load( glbUrl, function ( gltf ) {


			
    		gltfs = gltf;
    		gltfs.scene.scale.set(1.8, 1.8, 1.8);
    		gltfs.scene.position.set(0, 0, 0);
    		scene.add(gltfs.scene);
    		animate();
    		//setupGui(container);
            // 调用回调函数
            if (onCompleteCallback) {
                onCompleteCallback();
            }
			
			

		}, undefined, function ( e ) {

			console.error( e );

		} );
	

	}


	function animate() {
		loopId = requestAnimationFrame(animate);
		gltfs.scene.rotation.y -= 0.01;
		renderer.render(scene, camera);
	}

	function setupGui(container) {

		effectController = {
			line: false,
		};

		const gui = new GUI();
		gui.add( effectController, 'line' ).name( 'display line' ).onChange( render3d );
		container.appendChild(gui.domElement);

	}
/*	function onWindowResize() {

		const canvasWidth = window.innerWidth;
		const canvasHeight = window.innerHeight;

		renderer.setSize( canvasWidth, canvasHeight );

		camera.aspect = canvasWidth / canvasHeight;
		camera.updateProjectionMatrix();

		render3d();

	}*/
    function render3d() {

		// skybox is rendered separately, so that it is always behind the teapot.
		/*if ( shading === 'reflective' ) {

			scene.background = textureCube;

		} else {

			scene.background = null;

		}*/

		renderer.render( scene, camera );

	}

	
	export { init3d, render3d };
