import { cp, mkdir } from 'node:fs/promises'
import { fileURLToPath } from 'node:url'
import path from 'node:path'

const projectRoot = fileURLToPath(new URL('../', import.meta.url))
const deployRoot = path.join(projectRoot, 'dist')

await cp(path.join(projectRoot, 'src'), path.join(deployRoot, 'src'), {
  recursive: true,
})
await mkdir(path.join(deployRoot, 'data'), { recursive: true })
await cp(
  path.join(projectRoot, 'data', '.htaccess'),
  path.join(deployRoot, 'data', '.htaccess'),
)

console.log('Deployment files added to dist/.')
