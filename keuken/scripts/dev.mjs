import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const projectRoot = fileURLToPath(new URL('../', import.meta.url));
const xamppPhp = 'C:\\xampp\\php\\php.exe';
const phpExecutable = process.env.PHP_BINARY
  || (process.platform === 'win32' && existsSync(xamppPhp) ? xamppPhp : 'php');
const children = [];
let shuttingDown = false;

function stop(exitCode = 0) {
  if (shuttingDown) return;
  shuttingDown = true;
  for (const child of children) {
    if (child.exitCode === null) child.kill();
  }
  process.exitCode = exitCode;
}

function start(command, args, label) {
  const child = spawn(command, args, { cwd: projectRoot, stdio: 'inherit' });
  children.push(child);

  child.on('error', (error) => {
    console.error(`${label} could not start: ${error.message}`);
    if (label === 'PHP' && error.code === 'ENOENT') {
      console.error('Install PHP or set PHP_BINARY to the PHP executable.');
    }
    stop(1);
  });

  child.on('exit', (code) => {
    if (!shuttingDown) {
      if (code !== 0) console.error(`${label} exited with code ${code}.`);
      stop(code || 0);
    }
  });

  return child;
}

process.on('SIGINT', () => stop(0));
process.on('SIGTERM', () => stop(0));

start(phpExecutable, [
  '-S',
  '127.0.0.1:8000',
  '-t',
  projectRoot,
  path.join('src', 'php_router.php'),
], 'PHP');

start(process.execPath, [
  path.join('node_modules', 'vite', 'bin', 'vite.js'),
], 'Vite');
