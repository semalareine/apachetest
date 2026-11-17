import { spawn } from 'node:child_process'
import { fileURLToPath } from 'node:url'

const projectRoot = fileURLToPath(new URL('../', import.meta.url))
let php
let vite
let shuttingDown = false

function stop(exitCode = 0) {
  if (shuttingDown) return
  shuttingDown = true
  process.exitCode = exitCode
  php?.kill()
  vite?.kill()
}

if (!process.env.PHP_BACKEND_URL) {
  php = spawn(process.env.PHP_BINARY || 'php', [
    '-S',
    '127.0.0.1:8000',
    '-t',
    '.',
    'src/php_router.php',
  ], { cwd: projectRoot, stdio: 'inherit' })

  php.on('error', (error) => {
    console.error(
      `Could not start PHP: ${error.message}. Install PHP with PDO SQLite, or set PHP_BACKEND_URL to an existing PHP server.`,
    )
    stop(1)
  })

  php.on('exit', (code) => {
    if (!shuttingDown) {
      console.error(`The PHP server stopped${code ? ` with exit code ${code}` : ''}.`)
      stop(code || 1)
    }
  })
}

vite = spawn(process.execPath, [
  'node_modules/vite/bin/vite.js',
  ...process.argv.slice(2),
], { cwd: projectRoot, stdio: 'inherit' })

vite.on('error', (error) => {
  console.error(`Could not start Vite: ${error.message}`)
  stop(1)
})

vite.on('exit', (code) => {
  if (!shuttingDown) stop(code || 0)
})

for (const signal of ['SIGINT', 'SIGTERM']) {
  process.on(signal, () => stop())
}
