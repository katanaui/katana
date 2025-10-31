const esbuild = require('esbuild');
const path = require('path');
const fs = require('fs');

const isProduction = process.env.NODE_ENV === 'production';
const isWatch = process.argv.includes('--watch');

// Ensure public/katana directory exists
const outputDir = path.join(__dirname, 'public', 'katana');
if (!fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir, { recursive: true });
}

// Find all JS files in resources/js/katana/
const sourceDir = path.join(__dirname, 'resources', 'js', 'katana');
const entryPoints = {};

if (fs.existsSync(sourceDir)) {
  const files = fs.readdirSync(sourceDir).filter(file => file.endsWith('.js'));
  files.forEach(file => {
    const name = file.replace('.js', '');
    entryPoints[name] = path.join(sourceDir, file);
  });
}

const buildOptions = {
  entryPoints,
  outdir: outputDir,
  format: 'iife',
  bundle: true,
  sourcemap: !isProduction,
  minify: isProduction,
  platform: 'browser',
};

async function build() {
  try {
    console.log(`Building component JavaScript files... (${isProduction ? 'production' : 'development'})`);

    if (isWatch) {
      const ctx = await esbuild.context(buildOptions);
      await ctx.watch();
      console.log('Watching for changes in resources/js/katana/...');
    } else {
      await esbuild.build(buildOptions);
      console.log(`✓ Successfully built component files to ${outputDir}`);
    }
  } catch (err) {
    console.error('Build failed:', err);
    process.exit(1);
  }
}

build();
