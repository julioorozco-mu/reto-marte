const countdown = document.querySelector("#countdown");
const signalText = document.querySelector("#signalText");
const hero = document.querySelector(".hero");
const appDialog = document.querySelector("#appDialog");
const appDialogTitle = document.querySelector("#appDialogTitle");
const appDialogMessage = document.querySelector("#appDialogMessage");
const appDialogCloseButton = document.querySelector("#appDialogClose");
const canvas = document.querySelector("#heroCanvas");
const ctx = canvas?.getContext("2d", { alpha: true });
const launchSignalText = "Cuenta regresiva rumbo al evento";
const canHover = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
let heroActive = true;
let videoPlaying = false;
let parallaxFrame = 0;

function hideAppDialog() {
  if (!appDialog) return;
  appDialog.hidden = true;
  appDialog.classList.remove("app-dialog--error");
}

function showAppDialog(message, options = {}) {
  if (!appDialog || !appDialogTitle || !appDialogMessage) {
    window.alert(message);
    return;
  }

  const type = options.type === "error" ? "error" : "success";
  appDialogTitle.textContent = options.title || (type === "error" ? "No se pudo completar" : "Registro completado");
  appDialogMessage.textContent = String(message || "Operacion completada.");
  appDialog.classList.toggle("app-dialog--error", type === "error");
  appDialog.hidden = false;
  appDialogCloseButton?.focus();
}

appDialogCloseButton?.addEventListener("click", hideAppDialog);
appDialog?.addEventListener("click", (event) => {
  const target = event.target;
  if (target instanceof HTMLElement && target.dataset.closeDialog === "true") {
    hideAppDialog();
  }
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") hideAppDialog();
});

if (hero) {
  document.body.style.setProperty("--mx", "0px");
  document.body.style.setProperty("--my", "0px");
}

if (hero && "IntersectionObserver" in window) {
  const observer = new IntersectionObserver(([entry]) => {
    heroActive = entry.isIntersecting;
    document.body.classList.toggle("hero-paused", !heroActive || videoPlaying);
    if (heroActive && !videoPlaying) startCanvas();
  }, { threshold: 0.08 });
  observer.observe(hero);
}

const eventStart = new Date(2026, 8, 8, 0, 0, 0);
function tickCountdown() {
  const remaining = Math.max(0, eventStart.getTime() - Date.now());
  const hours = Math.floor(remaining / 3600000).toString().padStart(4, "0");
  const minutes = Math.floor((remaining % 3600000) / 60000).toString().padStart(2, "0");
  const seconds = Math.floor((remaining % 60000) / 1000).toString().padStart(2, "0");
  if (countdown) countdown.textContent = `RM-${hours}:${minutes}:${seconds}`;
}
setInterval(tickCountdown, 1000);
tickCountdown();
if (signalText) signalText.textContent = launchSignalText;

let particles = [];
let shootingStars = [];
let canvasFrame = 0;
let lastDraw = 0;
let nextMeteorAt = 0;

function resizeCanvas() {
  if (!canvas || !ctx) return;
  const ratio = Math.min(window.devicePixelRatio || 1, 1.5);
  const rect = canvas.getBoundingClientRect();
  canvas.width = Math.max(1, Math.floor(rect.width * ratio));
  canvas.height = Math.max(1, Math.floor(rect.height * ratio));
  ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
  const width = rect.width;
  const height = rect.height;
  const amount = Math.min(86, Math.max(42, Math.floor(width / 18)));
  particles = Array.from({ length: amount }, () => ({
    x: Math.random() * width,
    y: Math.random() * height,
    vx: (Math.random() - 0.5) * 0.24,
    vy: Math.random() * 0.18 + 0.055,
    r: Math.random() * 1.45 + 0.65,
    a: Math.random() * 0.55 + 0.3,
    pulse: Math.random() * Math.PI * 2
  }));
}

function spawnMeteor(width, height) {
  const fromLeft = Math.random() > 0.28;
  const speed = Math.random() * 6 + 8;
  const length = Math.random() * 90 + 150;
  shootingStars.push({
    x: fromLeft ? Math.random() * width * 0.5 - 90 : width + 80,
    y: Math.random() * height * 0.32 + 35,
    vx: fromLeft ? speed : -speed,
    vy: speed * (Math.random() * 0.28 + 0.22),
    life: 1,
    decay: Math.random() * 0.008 + 0.012,
    length,
    hue: Math.random() > 0.45 ? "101,225,255" : "255,191,105"
  });
}

function drawMeteors(width, height, time) {
  if (time > nextMeteorAt && shootingStars.length < 3) {
    spawnMeteor(width, height);
    nextMeteorAt = time + Math.random() * 2200 + 1800;
  }

  shootingStars = shootingStars.filter((star) => star.life > 0 && star.x < width + 220 && star.y < height + 160);
  shootingStars.forEach((star) => {
    star.x += star.vx;
    star.y += star.vy;
    star.life -= star.decay;
    const angle = Math.atan2(star.vy, star.vx);
    const tailX = star.x - Math.cos(angle) * star.length;
    const tailY = star.y - Math.sin(angle) * star.length;
    const glow = Math.max(0, star.life);

    const gradient = ctx.createLinearGradient(star.x, star.y, tailX, tailY);
    gradient.addColorStop(0, `rgba(255,255,255,${0.95 * glow})`);
    gradient.addColorStop(0.22, `rgba(${star.hue},${0.78 * glow})`);
    gradient.addColorStop(1, `rgba(${star.hue},0)`);
    ctx.beginPath();
    ctx.strokeStyle = gradient;
    ctx.lineWidth = 2.2;
    ctx.moveTo(star.x, star.y);
    ctx.lineTo(tailX, tailY);
    ctx.stroke();

    ctx.beginPath();
    ctx.fillStyle = `rgba(255,255,255,${0.85 * glow})`;
    ctx.shadowColor = `rgba(${star.hue},.95)`;
    ctx.shadowBlur = 16;
    ctx.arc(star.x, star.y, 2.4, 0, Math.PI * 2);
    ctx.fill();
    ctx.shadowBlur = 0;
  });
}

function drawCanvas(time = 0) {
  canvasFrame = 0;
  if (!canvas || !ctx || reduceMotion || !heroActive || videoPlaying || document.hidden) return;
  if (time - lastDraw < 30) {
    startCanvas();
    return;
  }
  lastDraw = time;
  const width = canvas.clientWidth;
  const height = canvas.clientHeight;
  ctx.clearRect(0, 0, width, height);
  particles.forEach((p, index) => {
    p.x += p.vx;
    p.y += p.vy;
    p.pulse += 0.025;
    if (p.y > height + 16) p.y = -16;
    if (p.x < -16) p.x = width + 16;
    if (p.x > width + 16) p.x = -16;
    const alpha = p.a + Math.sin(p.pulse) * 0.16;
    ctx.beginPath();
    ctx.fillStyle = `rgba(136,235,255,${Math.max(0.22, alpha)})`;
    ctx.shadowColor = "rgba(101,225,255,.65)";
    ctx.shadowBlur = 7;
    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
    ctx.fill();
    ctx.shadowBlur = 0;

    for (let i = index + 1; i < particles.length; i += 2) {
      const other = particles[i];
      const dx = p.x - other.x;
      const dy = p.y - other.y;
      const distance = Math.hypot(dx, dy);
      if (distance < 92) {
        ctx.beginPath();
        ctx.strokeStyle = `rgba(101,225,255,${0.105 * (1 - distance / 92)})`;
        ctx.lineWidth = 1;
        ctx.moveTo(p.x, p.y);
        ctx.lineTo(other.x, other.y);
        ctx.stroke();
      }
    }
  });
  drawMeteors(width, height, time);
  startCanvas();
}

function startCanvas() {
  if (!canvasFrame && canvas && ctx && !reduceMotion && heroActive && !videoPlaying && !document.hidden) {
    canvasFrame = requestAnimationFrame(drawCanvas);
  }
}


let heroTextReplayed = false;

function replayHeroTextReveal() {
  if (!hero || reduceMotion || heroTextReplayed) return;
  heroTextReplayed = true;
  hero.classList.add("text-reveal-on");
}

if (hero && !reduceMotion) {
  if (heroActive) replayHeroTextReveal();
  if ("IntersectionObserver" in window) {
    const textRevealObserver = new IntersectionObserver(([entry]) => {
      if (entry.isIntersecting) replayHeroTextReveal();
    }, { threshold: 0.35 });
    textRevealObserver.observe(hero);
  }
}
if (canvas && ctx && !reduceMotion) {
  window.addEventListener("resize", resizeCanvas, { passive: true });
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) startCanvas();
  });
  resizeCanvas();
  startCanvas();
}

// Rotating Mars for the program section.
const marsCanvas = document.querySelector("#mars3dCanvas");
const marsCtx = marsCanvas?.getContext("2d", { alpha: true });
let marsFrame = 0;
let marsVisible = true;
let marsTexture;
let marsStars = [];
let marsPhotoReady = false;
const marsPhoto = new Image();
marsPhoto.onload = () => {
  marsPhotoReady = true;
  startMarsCanvas();
};
marsPhoto.src = "assets/marte.png";

function marsNoise(x, y) {
  const n = Math.sin(x * 12.9898 + y * 78.233) * 43758.5453;
  return n - Math.floor(n);
}

function createMarsTexture() {
  const texture = document.createElement("canvas");
  texture.width = 1800;
  texture.height = 900;
  const tctx = texture.getContext("2d");
  const image = tctx.createImageData(texture.width, texture.height);
  const data = image.data;

  for (let y = 0; y < texture.height; y += 1) {
    const v = y / texture.height;
    const latShade = Math.pow(Math.abs(v - 0.52) * 2, 1.35);
    for (let x = 0; x < texture.width; x += 1) {
      const u = x / texture.width;
      const terrain =
        marsNoise(x * 0.003, y * 0.003) * 0.52 +
        marsNoise(x * 0.009 + 9, y * 0.008) * 0.28 +
        marsNoise(x * 0.024 + 21, y * 0.021 + 7) * 0.2;
      const highlands = Math.sin(u * Math.PI * 4.1 + Math.sin(v * 8) * 0.8) * 0.08;
      const dust = Math.sin((u + v * 0.18) * Math.PI * 12) * 0.035;
      const tone = Math.max(0, Math.min(1, terrain + highlands + dust - latShade * 0.08));
      const i = (y * texture.width + x) * 4;
      data[i] = 104 + tone * 96;
      data[i + 1] = 53 + tone * 70;
      data[i + 2] = 34 + tone * 44;
      data[i + 3] = 255;
    }
  }
  tctx.putImageData(image, 0, 0);

  tctx.globalCompositeOperation = "multiply";
  const darkBasins = [
    [0.18, 0.34, 240, 78, -0.28, .34],
    [0.38, 0.52, 360, 42, 0.02, .42],
    [0.53, 0.50, 430, 34, -0.04, .38],
    [0.71, 0.43, 250, 65, 0.22, .24],
    [0.84, 0.58, 180, 50, -0.18, .28]
  ];
  darkBasins.forEach(([u, v, rx, ry, rot, alpha]) => {
    tctx.save();
    tctx.translate(u * texture.width, v * texture.height);
    tctx.rotate(rot);
    const dark = tctx.createRadialGradient(0, 0, 0, 0, 0, rx);
    dark.addColorStop(0, `rgba(42,24,18,${alpha})`);
    dark.addColorStop(0.72, `rgba(52,27,20,${alpha * 0.48})`);
    dark.addColorStop(1, "rgba(255,255,255,0)");
    tctx.fillStyle = dark;
    tctx.beginPath();
    tctx.ellipse(0, 0, rx, ry, 0, 0, Math.PI * 2);
    tctx.fill();
    tctx.restore();
  });
  tctx.globalCompositeOperation = "source-over";

  for (let i = 0; i < 7; i += 1) {
    const y = texture.height * (0.46 + Math.sin(i * 1.25) * 0.035);
    tctx.beginPath();
    tctx.moveTo(texture.width * 0.14, y);
    for (let x = texture.width * 0.14; x <= texture.width * 0.73; x += 28) {
      const drift = Math.sin(x * 0.011 + i) * 18 + Math.sin(x * 0.039 + i * 2.1) * 8;
      tctx.lineTo(x, y + drift + i * 7);
    }
    tctx.strokeStyle = i < 4 ? "rgba(43,19,13,.42)" : "rgba(232,160,98,.11)";
    tctx.lineWidth = i < 4 ? 6 - i * 0.7 : 2;
    tctx.lineCap = "round";
    tctx.stroke();
  }

  for (let i = 0; i < 82; i += 1) {
    const x = Math.random() * texture.width;
    const y = Math.random() * texture.height;
    const r = Math.random() * 13 + (Math.random() > 0.9 ? 24 : 3);
    const crater = tctx.createRadialGradient(x - r * 0.32, y - r * 0.3, r * 0.08, x, y, r);
    crater.addColorStop(0, "rgba(255,228,176,.18)");
    crater.addColorStop(0.38, "rgba(75,39,27,.2)");
    crater.addColorStop(0.64, "rgba(21,11,8,.34)");
    crater.addColorStop(0.78, "rgba(232,180,122,.16)");
    crater.addColorStop(1, "rgba(255,255,255,0)");
    tctx.fillStyle = crater;
    tctx.beginPath();
    tctx.ellipse(x, y, r * (1.08 + Math.random() * 0.22), r * (0.78 + Math.random() * 0.18), Math.random() * Math.PI, 0, Math.PI * 2);
    tctx.fill();
  }


  tctx.save();
  tctx.globalCompositeOperation = "soft-light";
  for (let i = 0; i < 36; i += 1) {
    const y = texture.height * (0.16 + Math.random() * 0.68);
    const start = Math.random() * texture.width;
    tctx.beginPath();
    tctx.moveTo(start, y);
    for (let x = start; x < start + texture.width * (0.16 + Math.random() * 0.22); x += 34) {
      tctx.lineTo(x, y + Math.sin(x * 0.018 + i) * 10 + Math.sin(x * 0.047) * 4);
    }
    tctx.strokeStyle = Math.random() > 0.45 ? "rgba(250,196,128,.12)" : "rgba(47,23,17,.14)";
    tctx.lineWidth = Math.random() * 8 + 3;
    tctx.lineCap = "round";
    tctx.stroke();
  }
  tctx.restore();
  const polarTop = tctx.createLinearGradient(0, 0, 0, texture.height * 0.18);
  polarTop.addColorStop(0, "rgba(230,222,198,.4)");
  polarTop.addColorStop(1, "rgba(230,222,198,0)");
  tctx.fillStyle = polarTop;
  tctx.fillRect(0, 0, texture.width, texture.height * 0.18);

  const polarBottom = tctx.createLinearGradient(0, texture.height, 0, texture.height * 0.82);
  polarBottom.addColorStop(0, "rgba(214,204,180,.2)");
  polarBottom.addColorStop(1, "rgba(214,204,180,0)");
  tctx.fillStyle = polarBottom;
  tctx.fillRect(0, texture.height * 0.82, texture.width, texture.height * 0.18);

  return texture;
}

function resizeMarsCanvas() {
  if (!marsCanvas || !marsCtx) return;
  const rect = marsCanvas.getBoundingClientRect();
  const ratio = Math.min(window.devicePixelRatio || 1, 1.5);
  marsCanvas.width = Math.max(1, Math.floor(rect.width * ratio));
  marsCanvas.height = Math.max(1, Math.floor(rect.height * ratio));
  marsCtx.setTransform(ratio, 0, 0, ratio, 0, 0);
  marsStars = Array.from({ length: Math.max(24, Math.floor(rect.width / 34)) }, () => ({
    x: Math.random() * rect.width,
    y: Math.random() * rect.height,
    r: Math.random() * 1.4 + 0.4,
    a: Math.random() * 0.5 + 0.2
  }));
}

function drawMarsBackground(width, height) {
  const bg = marsCtx.createLinearGradient(0, 0, width, height);
  bg.addColorStop(0, "rgba(3,6,12,.92)");
  bg.addColorStop(0.52, "rgba(7,14,24,.58)");
  bg.addColorStop(1, "rgba(35,17,12,.32)");
  marsCtx.fillStyle = bg;
  marsCtx.fillRect(0, 0, width, height);
  marsStars.forEach((star) => {
    marsCtx.beginPath();
    marsCtx.fillStyle = `rgba(220,245,255,${star.a})`;
    marsCtx.arc(star.x, star.y, star.r, 0, Math.PI * 2);
    marsCtx.fill();
  });
}

function drawRotatingMars(time = 0) {
  marsFrame = 0;
  if (!marsCanvas || !marsCtx || reduceMotion || !marsVisible || document.hidden) return;
  const width = marsCanvas.clientWidth;
  const height = marsCanvas.clientHeight;
  drawMarsBackground(width, height);

  if (marsPhotoReady) {
    const imgRatio = marsPhoto.naturalWidth / marsPhoto.naturalHeight;
    const breath = 1.045 + Math.sin(time * 0.00022) * 0.012;
    let drawW = width * breath;
    let drawH = drawW / imgRatio;
    if (drawH < height * 1.04) {
      drawH = height * 1.04 * breath;
      drawW = drawH * imgRatio;
    }

    const driftX = Math.sin(time * 0.00018) * Math.min(28, width * 0.025);
    const driftY = Math.cos(time * 0.00014) * Math.min(14, height * 0.018);
    const x = width - drawW + width * (width > 900 ? 0.035 : 0.16) + driftX;
    const y = (height - drawH) * 0.48 + driftY;

    marsCtx.save();
    marsCtx.globalAlpha = 0.98;
    marsCtx.filter = "saturate(1.08) contrast(1.06) brightness(.94)";
    marsCtx.drawImage(marsPhoto, x, y, drawW, drawH);
    marsCtx.restore();

    marsCtx.save();
    marsCtx.globalCompositeOperation = "screen";
    const rim = marsCtx.createRadialGradient(width * 0.74, height * 0.36, height * 0.1, width * 0.74, height * 0.36, height * 0.62);
    rim.addColorStop(0, "rgba(255,134,75,.18)");
    rim.addColorStop(0.5, "rgba(255,134,75,.08)");
    rim.addColorStop(1, "rgba(255,134,75,0)");
    marsCtx.fillStyle = rim;
    marsCtx.fillRect(0, 0, width, height);
    marsCtx.restore();
  } else {
    marsCtx.fillStyle = "rgba(255,121,70,.08)";
    marsCtx.beginPath();
    marsCtx.arc(width * 0.78, height * 0.48, Math.min(width, height) * 0.42, 0, Math.PI * 2);
    marsCtx.fill();
  }

  const textShade = marsCtx.createLinearGradient(0, 0, width, 0);
  textShade.addColorStop(0, "rgba(3,7,14,.98)");
  textShade.addColorStop(0.34, "rgba(3,7,14,.82)");
  textShade.addColorStop(0.58, "rgba(3,7,14,.34)");
  textShade.addColorStop(1, "rgba(3,7,14,.1)");
  marsCtx.fillStyle = textShade;
  marsCtx.fillRect(0, 0, width, height);

  const lowerShade = marsCtx.createLinearGradient(0, height * 0.46, 0, height);
  lowerShade.addColorStop(0, "rgba(3,7,14,0)");
  lowerShade.addColorStop(1, "rgba(3,7,14,.74)");
  marsCtx.fillStyle = lowerShade;
  marsCtx.fillRect(0, 0, width, height);

  startMarsCanvas();
}
function startMarsCanvas() {
  if (!marsFrame && marsCanvas && marsCtx && !reduceMotion && marsVisible && !document.hidden) {
    marsFrame = requestAnimationFrame(drawRotatingMars);
  }
}

if (marsCanvas && marsCtx && !reduceMotion) {
  resizeMarsCanvas();
  window.addEventListener("resize", resizeMarsCanvas, { passive: true });
  if ("IntersectionObserver" in window) {
    const marsObserver = new IntersectionObserver(([entry]) => {
      marsVisible = entry.isIntersecting;
      if (marsVisible) startMarsCanvas();
    }, { threshold: 0.05 });
    marsObserver.observe(marsCanvas);
  }
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) startMarsCanvas();
  });
  startMarsCanvas();
}

// Cinematic media reveals (STEAM image + falling meteor).
const steamImage = document.querySelector(".steam-white-image");
const meteorSection = document.querySelector(".meteor-hero");
if ("IntersectionObserver" in window && !reduceMotion) {
  if (steamImage) {
    const steamObserver = new IntersectionObserver(([entry]) => {
      if (entry.isIntersecting) {
        steamImage.classList.add("steam-enter");
        steamObserver.disconnect();
      }
    }, { threshold: 0.35, rootMargin: "0px 0px -8% 0px" });
    steamObserver.observe(steamImage);
  }

  if (meteorSection) {
    meteorSection.classList.remove("meteor-impact");
  }
} else {
  if (steamImage) steamImage.classList.add("steam-enter");
  if (meteorSection) meteorSection.classList.remove("meteor-impact");
}

// Reveal convocatoria-style cards as they enter the viewport.
const techCards = document.querySelectorAll(".intro-item, .mission-card, .mission-detail, .score-board, .signup-form");
if (techCards.length) {
  if ("IntersectionObserver" in window && !reduceMotion) {
    const techCardRevealObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          techCardRevealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.18, rootMargin: "0px 0px -6% 0px" });
    techCards.forEach((card, index) => {
      card.style.transitionDelay = `${Math.min(index * 45, 240)}ms`;
      techCardRevealObserver.observe(card);
    });
  } else {
    techCards.forEach((card) => card.classList.add("is-visible"));
  }
}
// Premium floating meteor interaction with spring smoothing.
const meteorHero = document.querySelector(".meteor-hero");
if (meteorHero && !reduceMotion) {
  const state = { x: 0, y: 0, rx: 0, ry: 0, sx: 50, sy: 42 };
  const target = { x: 0, y: 0, rx: 0, ry: 0, sx: 50, sy: 42 };
  const velocity = { x: 0, y: 0, rx: 0, ry: 0, sx: 0, sy: 0 };
  let meteorFrame = 0;
  let interacting = false;

  function springStep(value, goal, speed, stiffness = 0.09, damping = 0.72) {
    const force = (goal - value) * stiffness;
    const nextSpeed = (speed + force) * damping;
    return [value + nextSpeed, nextSpeed];
  }

  function animateMeteor() {
    [state.x, velocity.x] = springStep(state.x, target.x, velocity.x);
    [state.y, velocity.y] = springStep(state.y, target.y, velocity.y);
    [state.rx, velocity.rx] = springStep(state.rx, target.rx, velocity.rx);
    [state.ry, velocity.ry] = springStep(state.ry, target.ry, velocity.ry);
    [state.sx, velocity.sx] = springStep(state.sx, target.sx, velocity.sx, 0.12, 0.76);
    [state.sy, velocity.sy] = springStep(state.sy, target.sy, velocity.sy, 0.12, 0.76);

    if (!interacting) {
      const t = performance.now() * 0.00022;
      target.ry = Math.sin(t) * 2.4;
      target.rx = Math.cos(t * 0.9) * 1.5;
      target.x = Math.sin(t * 1.1) * 4;
      target.y = Math.cos(t) * 3;
    }

    meteorHero.style.setProperty("--tx", `${state.x.toFixed(2)}px`);
    meteorHero.style.setProperty("--ty", `${state.y.toFixed(2)}px`);
    meteorHero.style.setProperty("--rx", `${state.rx.toFixed(2)}deg`);
    meteorHero.style.setProperty("--ry", `${state.ry.toFixed(2)}deg`);
    meteorHero.style.setProperty("--sx", `${state.sx.toFixed(1)}%`);
    meteorHero.style.setProperty("--sy", `${state.sy.toFixed(1)}%`);
    meteorFrame = requestAnimationFrame(animateMeteor);
  }

  meteorHero.addEventListener("pointermove", (event) => {
    interacting = true;
    const rect = meteorHero.getBoundingClientRect();
    const px = (event.clientX - rect.left) / rect.width;
    const py = (event.clientY - rect.top) / rect.height;
    const dx = px - 0.5;
    const dy = py - 0.5;
    target.x = dx * 24;
    target.y = dy * 18;
    target.ry = dx * 12;
    target.rx = dy * -10;
    target.sx = px * 100;
    target.sy = py * 100;
  }, { passive: true });

  meteorHero.addEventListener("pointerleave", () => {
    interacting = false;
    target.sx = 50;
    target.sy = 42;
  }, { passive: true });

  meteorFrame = requestAnimationFrame(animateMeteor);
  document.addEventListener("visibilitychange", () => {
    if (document.hidden && meteorFrame) cancelAnimationFrame(meteorFrame);
    if (!document.hidden) meteorFrame = requestAnimationFrame(animateMeteor);
  });
}

// Institution filter form behavior.
const institutionForm = document.querySelector(".institution-form");
if (institutionForm) {
  const institutionCards = Array.from(institutionForm.querySelectorAll(".institution-card"));
  const institutionPanels = Array.from(institutionForm.querySelectorAll(".institution-panel"));
  const emptyState = institutionForm.querySelector("#institutionEmpty");
  const submitButton = institutionForm.querySelector(".institution-submit");
  const uploadRules = [
    {
      input: institutionForm.querySelector('input[name="unach_curp_file"]'),
      maxBytes: 2 * 1024 * 1024,
      allowedTypes: ["application/pdf", "image/jpeg", "image/png"],
      allowedExtensions: [".pdf", ".jpg", ".jpeg", ".png"],
      invalidTypeMessage: "El archivo de CURP debe ser PDF, JPG o PNG.",
      invalidSizeMessage: "El archivo de CURP debe pesar 2 MB o menos.",
    },
    {
      input: institutionForm.querySelector('input[name="unach_study_certificate"]'),
      maxBytes: 2 * 1024 * 1024,
      allowedTypes: ["application/pdf", "image/jpeg", "image/png"],
      allowedExtensions: [".pdf", ".jpg", ".jpeg", ".png"],
      invalidTypeMessage: "El certificado de estudios debe ser PDF, JPG o PNG.",
      invalidSizeMessage: "El certificado de estudios debe pesar 2 MB o menos.",
    },
    {
      input: institutionForm.querySelector('input[name="cobach_responsiva"]'),
      maxBytes: 1024 * 1024,
      allowedTypes: ["application/pdf"],
      allowedExtensions: [".pdf"],
      invalidTypeMessage: "Solo se permite un archivo PDF para la carta responsiva.",
      invalidSizeMessage: "La carta responsiva debe pesar 1 MB o menos.",
    },
  ];
  let selectedInstitution = "";

  function validateUploadFile(file, rule) {
    const byMime = rule.allowedTypes.includes(file.type);
    const fileName = file.name.toLowerCase();
    const byExtension = rule.allowedExtensions.some((ext) => fileName.endsWith(ext));
    const validType = byMime || byExtension;
    const validSize = file.size <= rule.maxBytes;
    if (!validType) return { valid: false, reason: rule.invalidTypeMessage };
    if (!validSize) return { valid: false, reason: rule.invalidSizeMessage };
    return { valid: true, reason: "" };
  }

  function updateRequiredFields() {
    institutionPanels.forEach((panel) => {
      const isActivePanel = panel.dataset.panel === selectedInstitution;
      const controls = Array.from(panel.querySelectorAll("input, select, textarea"));

      controls.forEach((control) => {
        if (control.disabled || control.readOnly || control.offsetParent === null) {
          control.required = false;
          return;
        }

        const type = (control.getAttribute("type") || "").toLowerCase();
        if (type === "hidden" || control.name.includes("last_name_2")) {
          control.required = false;
          return;
        }

        control.required = isActivePanel;
      });
    });
  }

  function syncInstitutionPanel(nextInstitution = "") {
    selectedInstitution = nextInstitution;
    institutionCards.forEach((card) => {
      const isActive = card.dataset.institution === selectedInstitution;
      card.classList.toggle("is-active", isActive);
      card.setAttribute("aria-pressed", String(isActive));
    });

    institutionPanels.forEach((panel) => {
      panel.hidden = panel.dataset.panel !== selectedInstitution;
    });

    updateRequiredFields();

    if (emptyState) emptyState.hidden = Boolean(selectedInstitution);
  }

  institutionCards.forEach((card) => {
    card.addEventListener("click", () => {
      syncInstitutionPanel(card.dataset.institution || "");
    });
  });

  const unachRoleRadios = document.querySelectorAll('input[name="unach_role"]');
  const unachStudentFields = document.getElementById('unach_student_fields');
  const unachTeacherFields = document.getElementById('unach_teacher_fields');
  const unachFormTitle = document.getElementById('unach_form_title');
  
  unachRoleRadios.forEach((radio) => {
    radio.addEventListener("change", (e) => {
      const role = e.target.value;
      if (role === 'estudiante') {
        if (unachStudentFields) unachStudentFields.style.display = '';
        if (unachTeacherFields) unachTeacherFields.style.display = 'none';
        if (unachFormTitle) unachFormTitle.textContent = 'Datos para estudiantes de UNACH';
      } else {
        if (unachStudentFields) unachStudentFields.style.display = 'none';
        if (unachTeacherFields) unachTeacherFields.style.display = '';
        if (unachFormTitle) unachFormTitle.textContent = 'Datos para docentes de UNACH';
      }
      updateRequiredFields();
    });
  });

  // Lógica de carga y filtrado dinámico de carreras de la UNACH
  const unachUnitSelect = institutionForm.querySelector('select[name="unach_unit"]');
  const unachMajorSelect = institutionForm.querySelector('select[name="unach_major"]');
  let carrerasData = null;

  async function loadCarreras() {
    try {
      const response = await fetch("carreras.json");
      if (!response.ok) throw new Error("No se pudo cargar la lista de carreras");
      const rawData = await response.json();
      carrerasData = {};
      // Normalizamos las llaves del JSON (reemplazar múltiples espacios por uno solo)
      for (const key in rawData) {
        const normalizedKey = key.replace(/\s+/g, " ").trim();
        carrerasData[normalizedKey] = rawData[key];
      }
    } catch (error) {
      console.error("Error cargando carreras:", error);
    }
  }

  function updateUnachMajors() {
    if (!unachMajorSelect) return;
    
    // Normalizamos el valor seleccionado (reemplazar múltiples espacios por uno solo)
    const selectedUnitRaw = unachUnitSelect ? unachUnitSelect.value.trim() : "";
    const selectedUnit = selectedUnitRaw.replace(/\s+/g, " ");
    
    // Limpiamos
    unachMajorSelect.innerHTML = "";
    
    if (!selectedUnit || !carrerasData || !carrerasData[selectedUnit]) {
      const opt = document.createElement("option");
      opt.value = "";
      opt.textContent = "Selecciona primero una unidad académica";
      unachMajorSelect.appendChild(opt);
      unachMajorSelect.disabled = true;
      return;
    }
    
    // Habilitamos y agregamos opción por defecto
    unachMajorSelect.disabled = false;
    const defaultOpt = document.createElement("option");
    defaultOpt.value = "";
    defaultOpt.textContent = "Selecciona tu carrera";
    unachMajorSelect.appendChild(defaultOpt);
    
    // Inyectamos las carreras correspondientes
    carrerasData[selectedUnit].forEach((carrera) => {
      const opt = document.createElement("option");
      opt.value = carrera;
      opt.textContent = carrera;
      unachMajorSelect.appendChild(opt);
    });
  }

  // Cargamos el JSON
  loadCarreras();

  // Escuchamos el cambio de unidad académica
  if (unachUnitSelect) {
    unachUnitSelect.addEventListener("change", updateUnachMajors);
  }

  uploadRules.forEach((rule) => {
    rule.input?.addEventListener("change", () => {
      const file = rule.input.files?.[0];
      if (!file) return;
      const verdict = validateUploadFile(file, rule);
      if (!verdict.valid) {
        rule.input.value = "";
        showAppDialog(verdict.reason, { type: "error", title: "Archivo invalido" });
      }
    });
  });

  institutionForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!selectedInstitution) {
      showAppDialog("Selecciona primero si provienes de UNACH o COBACH.", { type: "error", title: "Institucion requerida" });
      institutionCards[0]?.focus();
      return;
    }

    if (!institutionForm.checkValidity()) {
      institutionForm.reportValidity();
      return;
    }

    const activePanel = institutionForm.querySelector(`.institution-panel[data-panel='${selectedInstitution}']`);
    const activeFileInputs = Array.from(activePanel?.querySelectorAll("input[type='file']") || []);
    for (const fileInput of activeFileInputs) {
      const rule = uploadRules.find((candidate) => candidate.input === fileInput);
      const file = fileInput.files?.[0];
      if (!rule || !file) continue;
      const verdict = validateUploadFile(file, rule);
      if (!verdict.valid) {
        showAppDialog(verdict.reason, { type: "error", title: "Archivo invalido" });
        fileInput.focus();
        return;
      }
    }

    const formData = new FormData(institutionForm);
    formData.set("institution", selectedInstitution);

    if (submitButton) submitButton.disabled = true;

    try {
      const response = await fetch("api/register_participant.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json().catch(() => ({
        ok: false,
        message: "No se pudo procesar la respuesta del servidor.",
      }));

      if (!response.ok || !result.ok) {
        showAppDialog(result.message || "No se pudo guardar tu registro.", { type: "error" });
        return;
      }

      showAppDialog("Registro guardado correctamente.", { type: "success", title: "Mision inicializada" });
      institutionForm.reset();
      syncInstitutionPanel("");
    } catch (error) {
      showAppDialog("No se pudo conectar con el servidor. Verifica XAMPP y vuelve a intentar.", { type: "error" });
    } finally {
      if (submitButton) submitButton.disabled = false;
    }
  });

  function calculateAge(dateString) {
    if (!dateString) return "";
    const birthDate = new Date(`${dateString}T00:00:00`);
    if (Number.isNaN(birthDate.getTime())) return "";

    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    const dayDiff = today.getDate() - birthDate.getDate();

    if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
      age -= 1;
    }

    return age >= 0 ? String(age) : "";
  }

  const birthDateInputs = Array.from(institutionForm.querySelectorAll("input[type='date'][data-age-target]"));
  birthDateInputs.forEach((input) => {
    const targetName = input.dataset.ageTarget;
    const target = targetName ? institutionForm.querySelector(`input[name='${targetName}']`) : null;
    if (!target) return;

    const updateAge = () => {
      target.value = calculateAge(input.value);
    };

    input.addEventListener("input", updateAge);
    input.addEventListener("change", updateAge);
  });

  syncInstitutionPanel("");
}

// Space-tech scroll reveal (Modern 2026)
document.addEventListener("DOMContentLoaded", () => {
  const elements = document.querySelectorAll("h1, h2, h3, h4, p");
  
  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver((entries) => {
      let delay = 0;
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            entry.target.classList.add("space-revealed");
          }, delay * 80);
          delay++;
          observer.unobserve(entry.target);
        }
      });
    }, { rootMargin: "0px 0px -10% 0px", threshold: 0.1 });
    
    elements.forEach(el => {
      el.classList.add("space-reveal");
      observer.observe(el);
    });
  } else {
    elements.forEach(el => el.classList.add("space-revealed"));
  }

  // Scroll Spy (Active nav link by section)
  const sections = document.querySelectorAll("section[id]");
  const navLinks = document.querySelectorAll(".nav-links a");

  if ("IntersectionObserver" in window) {
    const spyObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const id = entry.target.getAttribute("id");
          navLinks.forEach((link) => {
            link.classList.toggle("active", link.getAttribute("href") === `#${id}`);
          });
        }
      });
    }, {
      rootMargin: "-30% 0px -60% 0px",
      threshold: 0
    });

    sections.forEach((section) => spyObserver.observe(section));
  }
});

// Native lazy-loading and animation pausing for YouTube video
const videoPanel = document.querySelector("#videoPanel");
const videoPlaceholder = document.querySelector("#videoPlaceholder");
if (videoPanel && videoPlaceholder) {
  videoPlaceholder.addEventListener("click", () => {
    // 1. Mark video as playing (will pause canvas loops)
    videoPlaying = true;
    document.body.classList.add("video-active");
    document.body.classList.add("hero-paused");

    // 2. Replace placeholder with real iframe
    videoPanel.innerHTML = `<iframe src="https://www.youtube.com/embed/-vbRyDh8xpo?autoplay=1&modestbranding=1&rel=0&iv_load_policy=3" title="Reproductor de video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
  });
}

