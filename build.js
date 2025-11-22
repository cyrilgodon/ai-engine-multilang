/**
 * Build System - AI Engine Multilang
 *
 * Système de build dual : DEV (non-obfusqué) + PROD (obfusqué)
 * Compatible avec le système de debug JS d'AI Engine Elevatio.
 *
 * @package AI_Engine_Multilang
 * @since 1.0.0
 */

const esbuild = require('esbuild');
const obfuscator = require('javascript-obfuscator');
const fs = require('fs');
const path = require('path');
const chokidar = require('chokidar');

// Configuration
const config = {
	inputDir: 'assets/js',
	outputDir: 'assets/js',
	files: [
		'conversation-handler.js',
	],
};

/**
 * Logger avec couleurs.
 */
const log = {
	info: (msg) => console.log(`\x1b[36m[INFO]\x1b[0m ${msg}`),
	success: (msg) => console.log(`\x1b[32m[SUCCESS]\x1b[0m ${msg}`),
	error: (msg) => console.error(`\x1b[31m[ERROR]\x1b[0m ${msg}`),
	warn: (msg) => console.warn(`\x1b[33m[WARN]\x1b[0m ${msg}`),
};

/**
 * Builder DEV : Minify SANS obfuscation.
 */
async function buildDev(file) {
	const inputPath = path.join(config.inputDir, file);
	const outputPath = path.join(config.outputDir, file.replace('.js', '.dev.min.js'));

	try {
		log.info(`Building DEV: ${file} → ${path.basename(outputPath)}`);

		await esbuild.build({
			entryPoints: [inputPath],
			outfile: outputPath,
			minify: true,
			bundle: false,
			target: ['es2015'],
			format: 'iife',
		});

		const stats = fs.statSync(outputPath);
		log.success(`DEV built: ${path.basename(outputPath)} (${Math.round(stats.size / 1024)}KB)`);
	} catch (err) {
		log.error(`DEV build failed for ${file}:`);
		console.error(err);
		throw err;
	}
}

/**
 * Builder PROD : Minify + Obfuscation.
 */
async function buildProd(file) {
	const inputPath = path.join(config.inputDir, file);
	const outputPath = path.join(config.outputDir, file.replace('.js', '.min.js'));

	try {
		log.info(`Building PROD: ${file} → ${path.basename(outputPath)}`);

		// Étape 1 : Minify avec esbuild
		const result = await esbuild.build({
			entryPoints: [inputPath],
			write: false,
			minify: true,
			bundle: false,
			target: ['es2015'],
			format: 'iife',
		});

		const minified = result.outputFiles[0].text;

		// Étape 2 : Obfuscation avec javascript-obfuscator
		const obfuscated = obfuscator.obfuscate(minified, {
			compact: true,
			controlFlowFlattening: false, // Plus rapide à l'exécution
			deadCodeInjection: false, // Moins de faux positifs antivirus
			stringArray: true,
			stringArrayEncoding: ['base64'],
			stringArrayThreshold: 0.75,
			renameGlobals: false, // Garder les noms de fonctions globales
			selfDefending: false, // Éviter les problèmes avec DevTools
		});

		// Écrire le fichier
		fs.writeFileSync(outputPath, obfuscated.getObfuscatedCode());

		const stats = fs.statSync(outputPath);
		log.success(`PROD built: ${path.basename(outputPath)} (${Math.round(stats.size / 1024)}KB)`);
	} catch (err) {
		log.error(`PROD build failed for ${file}:`);
		console.error(err);
		throw err;
	}
}

/**
 * Build ALL : DEV + PROD pour tous les fichiers.
 */
async function buildAll() {
	log.info('=== Building ALL files (DEV + PROD) ===');
	
	for (const file of config.files) {
		try {
			await buildDev(file);
			await buildProd(file);
		} catch (err) {
			log.error(`Failed to build ${file}`);
			process.exit(1);
		}
	}

	log.success('=== All files built successfully ===');
}

/**
 * Watch mode : Rebuild à chaque modification.
 */
function watch() {
	log.info('=== Watch mode started ===');
	log.info('Watching for changes in: ' + config.inputDir);

	const watcher = chokidar.watch(
		config.files.map((file) => path.join(config.inputDir, file)),
		{
			persistent: true,
			ignoreInitial: true,
		}
	);

	watcher.on('change', async (filePath) => {
		const fileName = path.basename(filePath);
		log.info(`File changed: ${fileName}`);

		try {
			await buildDev(fileName);
			await buildProd(fileName);
			log.success(`Rebuilt: ${fileName}`);
		} catch (err) {
			log.error(`Failed to rebuild ${fileName}`);
		}
	});

	log.info('Press Ctrl+C to stop watching');
}

/**
 * Clean : Supprimer les fichiers buildés.
 */
function clean() {
	log.info('=== Cleaning build files ===');

	for (const file of config.files) {
		const devFile = path.join(config.outputDir, file.replace('.js', '.dev.min.js'));
		const prodFile = path.join(config.outputDir, file.replace('.js', '.min.js'));

		if (fs.existsSync(devFile)) {
			fs.unlinkSync(devFile);
			log.info(`Deleted: ${path.basename(devFile)}`);
		}

		if (fs.existsSync(prodFile)) {
			fs.unlinkSync(prodFile);
			log.info(`Deleted: ${path.basename(prodFile)}`);
		}
	}

	log.success('=== Clean complete ===');
}

/**
 * CLI Parser et dispatcher.
 */
function main() {
	const args = process.argv.slice(2);
	const mode = args[0] || '--all';

	switch (mode) {
		case '--dev':
			config.files.forEach((file) => buildDev(file));
			break;

		case '--prod':
			config.files.forEach((file) => buildProd(file));
			break;

		case '--all':
			buildAll();
			break;

		case '--watch':
			buildAll().then(() => watch());
			break;

		case '--clean':
			clean();
			break;

		default:
			log.error(`Unknown mode: ${mode}`);
			console.log(`
Usage:
  node build.js [mode]

Modes:
  --dev    Build DEV only (.dev.min.js)
  --prod   Build PROD only (.min.js obfuscated)
  --all    Build DEV + PROD (default)
  --watch  Watch mode (auto-rebuild on change)
  --clean  Delete all built files
			`);
			process.exit(1);
	}
}

// Lancer le build
main();


