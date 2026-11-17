import { useEffect, useRef, useState } from 'react';
import { Canvas, useFrame, useThree } from '@react-three/fiber';
import { Model } from './model';
import { Bounds } from '@react-three/drei';
import { Vector3 } from 'three';
import {
  ChromaticAberration,
  EffectComposer,
  Noise,
  Pixelation,
  Vignette,
} from '@react-three/postprocessing';

const CAMERA_POSITION = [5, 12, 0];
const CAMERA_TARGET = [-10, 8, 0];
const BOOK_POSITION = [-11.752, 4.82, 4.749];
const BOOK_CAMERA_POSITION = [-10.7, 11.41, 4.749];
const NOTEBOOK_POSITION = [-15.313, 6.04, -18.104];
const NOTEBOOK_CAMERA_POSITION = [-14.7, 8.5, -17.2];
const REVIEW_BOARD_POSITION = [-22.032, 10.251, 16.578];
const REVIEW_BOARD_CAMERA_POSITION = [-22.032, 11.5, 3];
// peep 100 - 130 ivm 4de muur POTENTIALLY THO
const CAMERA_TURN_RANGE = (100 * Math.PI) / 180;
const CAMERA_TILT_RANGE = 0.25;
const CAMERA_SMOOTHING = 6;
const BOOK_FOCUS_DURATION = 1.2;
const NOTEBOOK_FOCUS_DURATION = 1.4;
const REVIEW_BOARD_FOCUS_DURATION = 1.4;

function CursorCamera({ pointer, focus, onFocusComplete }) {
  const { camera } = useThree();
  const offset = CAMERA_POSITION.map((value, index) => value - CAMERA_TARGET[index]);
  const radius = Math.hypot(...offset);
  const initialTheta = Math.atan2(offset[0], offset[2]);
  const initialPhi = Math.acos(offset[1] / radius);
  const angles = useRef({ theta: initialTheta, phi: initialPhi });
  const initialTarget = useRef(new Vector3(...CAMERA_TARGET));
  const cameraTarget = useRef(new Vector3());
  const cameraFocus = useRef(null);

  useFrame((_, delta) => {
    if (focus.current) {
      if (!cameraFocus.current) {
        const focusTarget = focus.current === 'notebook'
          ? { duration: NOTEBOOK_FOCUS_DURATION, position: NOTEBOOK_CAMERA_POSITION, target: NOTEBOOK_POSITION }
          : focus.current === 'reviewboard'
            ? {
              duration: REVIEW_BOARD_FOCUS_DURATION,
              position: REVIEW_BOARD_CAMERA_POSITION,
              target: REVIEW_BOARD_POSITION,
            }
            : { duration: BOOK_FOCUS_DURATION, position: BOOK_CAMERA_POSITION, target: BOOK_POSITION };
        cameraFocus.current = {
          elapsed: 0,
          duration: focusTarget.duration,
          fromPosition: camera.position.clone(),
          toPosition: new Vector3(...focusTarget.position),
          fromTarget: initialTarget.current.clone(),
          toTarget: new Vector3(...focusTarget.target),
        };
      }
      const currentFocus = cameraFocus.current;

      currentFocus.elapsed += delta;
      const progress = Math.min(currentFocus.elapsed / currentFocus.duration, 1);
      const easedProgress = progress * progress * (3 - 2 * progress);
      camera.position.lerpVectors(
        currentFocus.fromPosition,
        currentFocus.toPosition,
        easedProgress,
      );
      cameraTarget.current.lerpVectors(
        currentFocus.fromTarget,
        currentFocus.toTarget,
        easedProgress,
      );
      camera.lookAt(cameraTarget.current);

      if (progress === 1 && !currentFocus.completed) {
        currentFocus.completed = true;
        onFocusComplete();
      }
      return;
    }

    const smoothing = 1 - Math.exp(-CAMERA_SMOOTHING * delta);
    const theta = initialTheta - pointer.current.x * (CAMERA_TURN_RANGE / 2);
    const phi = initialPhi + pointer.current.y * CAMERA_TILT_RANGE;

    angles.current.theta += (theta - angles.current.theta) * smoothing;
    angles.current.phi += (phi - angles.current.phi) * smoothing;

    const sinPhi = Math.sin(angles.current.phi);
    camera.position.set(
      CAMERA_TARGET[0] + radius * sinPhi * Math.sin(angles.current.theta),
      CAMERA_TARGET[1] + radius * Math.cos(angles.current.phi),
      CAMERA_TARGET[2] + radius * sinPhi * Math.cos(angles.current.theta),
    );
    camera.lookAt(...CAMERA_TARGET);
  });

  return null;
}

function KitchenScene() {
  const pointer = useRef({ x: 0, y: 0 });
  const focus = useRef(null);
  const navigationTimer = useRef(null);

  useEffect(() => () => window.clearTimeout(navigationTimer.current), []);

  function updatePointer(event) {
    if (focus.current) return;
    const bounds = event.currentTarget.getBoundingClientRect();
    pointer.current.x = ((event.clientX - bounds.left) / bounds.width) * 2 - 1;
    pointer.current.y = ((event.clientY - bounds.top) / bounds.height) * 2 - 1;
  }

  function resetPointer() {
    pointer.current.x = 0;
    pointer.current.y = 0;
  }

  function focusBook() {
    focus.current = 'book';
  }

  function focusNotebook() {
    focus.current = 'notebook';
  }

  function focusReviewBoard() {
    focus.current = 'reviewboard';
  }

  function navigateToRecipes() {
    navigationTimer.current = window.setTimeout(() => {
      const destination = focus.current === 'notebook'
        ? '/src/profile.php'
        : focus.current === 'reviewboard'
          ? '/src/reviewboard.php'
          : '/src/recipes.php';
      window.location.assign(destination);
    }, 800);
  }

  return (
    <div
      onPointerMove={updatePointer}
      onPointerLeave={resetPointer}
      style={{
        position: 'fixed',
        top: 0,
        left: 0,
        width: '100vw',
        height: '100vh',
        overflow: 'hidden',
        zIndex: 1,
      }}
    >
      <Canvas
        camera={{ fov: 60, near: 0.1, far: 500, position: CAMERA_POSITION }}
        fallback={
          <main className="scene-fallback">
            <h1>De 3D-keuken is niet beschikbaar</h1>
            <p>Schakel WebGL of hardwareversnelling in, of open het receptenboek rechtstreeks.</p>
            <a href="/src/recipes.php">Open het receptenboek</a>
          </main>
        }
      >
        <color attach="background" args={['#0c1020']} />
        <fog attach="fog" args={['#111522', 32, 90]} />
        <ambientLight color="#8492b8" intensity={0.7} />
        <directionalLight
          color="#b9c9ff"
          position={[8, 15, 10]}
          intensity={1.8}
        />
        <pointLight
          color="#ff704a"
          position={[-14, 10, 4]}
          intensity={25}
          distance={40}
          decay={2}
        />

        <Bounds>
          <Model
            onBookClick={focusBook}
            onNotepadClick={focusNotebook}
            onReviewBoardClick={focusReviewBoard}
          />
        </Bounds>

        <CursorCamera
          pointer={pointer}
          focus={focus}
          onFocusComplete={navigateToRecipes}
        />
        <EffectComposer multisampling={0}>
          <Pixelation granularity={2} />
          <Noise opacity={0.008} />
          <ChromaticAberration offset={[0.0004, 0.0004]} />
          <Vignette eskil={false} offset={0.18} darkness={0.45} />
        </EffectComposer>
      </Canvas>
    </div>
  );
}

function App() {
  const [access, setAccess] = useState('checking');

  useEffect(() => {
    let active = true;
    fetch('/src/session.php', { credentials: 'same-origin', cache: 'no-store' })
      .then((response) => {
        if (!response.ok) throw new Error('Could not verify your login.')
        return response.json()
      })
      .then(({ authenticated }) => {
        if (!active) return
        if (authenticated) {
          setAccess('allowed')
        } else {
          window.location.replace('/src/login.php')
        }
      })
      .catch(() => {
        if (active) setAccess('error')
      })

    return () => {
      active = false
    }
  }, [])

  if (access === 'allowed') return <KitchenScene />
  if (access === 'error') {
    return (
      <main className="access-message">
        <p>Kan je login niet controleren. Controleer of de PHP-server actief is.</p>
        <button type="button" onClick={() => window.location.reload()}>Opnieuw proberen</button>
      </main>
    )
  }
  return <main className="access-message" aria-live="polite">Keuken wordt geopend…</main>
}

export default App
