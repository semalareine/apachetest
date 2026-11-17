import { useRef, useState } from 'react'
import { Edges, useGLTF } from '@react-three/drei'
import { useFrame } from '@react-three/fiber'

function PulsingEdges() {
  const edge = useRef(null)

  useFrame(({ clock }) => {
    const pulse = (Math.sin(clock.elapsedTime * 5) + 1) / 2
    const material = edge.current?.material

    if (material) {
      material.linewidth = 1.5 + pulse * 4
      material.opacity = 0.2 + pulse * 0.8
      material.color.setRGB(1, 0.4 + pulse * 0.5, 0.05 + pulse * 0.35)
    }
  })

  return (
    <Edges
      ref={edge}
      color="#ff9f0a"
      lineWidth={1.5}
      opacity={1}
      transparent
      threshold={30}
    />
  )
}

export function Model({ onBookClick, onNotepadClick, ...props }) {
  const { nodes, materials } = useGLTF('/sinkfulllkitchenfinal.glb')
  const [bookHovered, setBookHovered] = useState(false)
  const [notepadHovered, setNotepadHovered] = useState(false)
  const [boardHovered, setBoardHovered] = useState(false)

  return (
    <group {...props} dispose={null}>
      <group position={[-13.883, 0, 0]}>
        <mesh
          castShadow
          receiveShadow
          geometry={nodes.Cube001.geometry}
          material={materials.walls}
        />
        <mesh
          castShadow
          receiveShadow
          geometry={nodes.Cube001_1.geometry}
          material={materials.ceiling}
        />
        <mesh
          castShadow
          receiveShadow
          geometry={nodes.Cube001_2.geometry}
          material={materials.floor}
        />
      </group>
      <group position={[36.576, 0, 0]}>
        <mesh
          castShadow
          receiveShadow
          geometry={nodes.Cube002.geometry}
          material={materials.kitchendemo}
        />
        <mesh
          castShadow
          receiveShadow
          geometry={nodes.Cube002_1.geometry}
          material={materials.countertops}
        />
        <mesh
          castShadow
          receiveShadow
          geometry={nodes.Cube002_2.geometry}
          material={materials.fridge}
        />
        <mesh
          castShadow
          receiveShadow
          geometry={nodes.Cube002_3.geometry}
          material={materials.shelf}
        />
        <mesh
          castShadow
          receiveShadow
          geometry={nodes.Cube002_4.geometry}
          material={materials.minishelf}
        />
        <mesh
          castShadow
          receiveShadow
          geometry={nodes.Cube002_5.geometry}
          material={materials.oven}
        />
        <mesh
          castShadow
          receiveShadow
          geometry={nodes.Cube002_6.geometry}
          material={materials.sink}
        />
      </group>
      <group position={[64.682, 0, 0]}>
        <mesh
          castShadow
          receiveShadow
          geometry={nodes.Cube003.geometry}
          material={materials.floor}
        />
        <mesh
          castShadow
          receiveShadow
          geometry={nodes.Cube003_1.geometry}
          material={materials.kitchendemo}
        />
        <group
          onPointerOver={(event) => {
            event.stopPropagation()
            setBoardHovered(true)
          }}
          onPointerOut={(event) => {
            event.stopPropagation()
            setBoardHovered(false)
          }}
        >
          <mesh
            castShadow
            receiveShadow
            geometry={nodes.Cube003_2.geometry}
            material={materials.board}
          >
            {boardHovered && <PulsingEdges />}
          </mesh>
          <mesh
            castShadow
            receiveShadow
            geometry={nodes.Cube003_3.geometry}
            material={materials.reviewboard}
          >
            {boardHovered && <PulsingEdges />}
          </mesh>
        </group>
      </group>
      <mesh
        castShadow
        receiveShadow
        geometry={nodes.table.geometry}
        material={materials.table}
        position={[-20.197, 4.169, 4.986]}
        scale={[6.702, 5.027, 8.365]}
      />
      <mesh
        castShadow
        receiveShadow
        geometry={nodes.book.geometry}
        material={materials.bookdecoy}
        position={[-14.788, 0.774, 1.105]}
        rotation={[-0.014, 0.374, 0.01]}
        scale={0.89}
        onPointerOver={(event) => {
          event.stopPropagation()
          setBookHovered(true)
        }}
        onPointerOut={(event) => {
          event.stopPropagation()
          setBookHovered(false)
        }}
        onClick={(event) => {
          event.stopPropagation()
          onBookClick?.()
        }}
      >
        {bookHovered && <PulsingEdges />}
      </mesh>
      <mesh
        castShadow
        receiveShadow
        geometry={nodes.notepad.geometry}
        material={materials['Material.001']}
        position={[-10.226, 6.04, -0.312]}
        rotation={[0, -0.471, 0]}
        onPointerOver={(event) => {
          event.stopPropagation()
          setNotepadHovered(true)
        }}
        onPointerOut={(event) => {
          event.stopPropagation()
          setNotepadHovered(false)
        }}
        onClick={(event) => {
          event.stopPropagation()
          onNotepadClick?.()
        }}
      >
        {notepadHovered && <PulsingEdges />}
      </mesh>
    </group>
  )
}

useGLTF.preload('/sinkfulllkitchenfinal.glb')