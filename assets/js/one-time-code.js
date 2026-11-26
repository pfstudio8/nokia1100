/* ══ One-time code ════════════════════════════════════════
   Six small cells and a caret. Digits rise into their cells;
   a right code draws the cells together until they fuse into
   one capsule that says so, and a wrong one sends a shiver
   down the row and drains the digits out.

   ── SIX CELLS, ONE FIELD ────────────────────────────────
   The same call the sign-in sheet makes, for the same reasons:
   six real inputs break paste, autofill from the notification,
   backspace and screen readers. So there is one invisible
   input over the row and the cells are drawn underneath it.

   ── THE MERGE IS THE GOO'S ──────────────────────────────
   The cells sit under a blur-and-threshold filter, far enough
   apart that it never bridges them. Verifying slides them to
   the middle while a capsule grows out of the same point: as
   they close, the filter joins them, and what is left is one
   shape. Nobody draws the in-between — it is the filter
   deciding when two things are close enough to be one.

   ── THE ANSWER IS A KNOB ────────────────────────────────
   A code nobody on the wall can know is not a demonstration.
   Accept and Reject are the two things the component does
   with a full row, so the panel picks which one you see.
   ════════════════════════════════════════════════════════ */

/* ── inlined from lab/motionkit ──────────────────────── */
/* ══ liquid ═══════════════════════════════════════════════
   Shared parts for the Framer Motion layer: one spring, one
   goo filter, one velocity→skew binding.

   ── why the filter is a hook ────────────────────────────
   The filter is embedded in each component's own return, but
   its id CANNOT be a literal. Every block here renders twice
   at once — once in the wall card, once in the detail overlay
   — and duplicate SVG ids do not scope, they collide: the
   second instance silently steals the first one's filter.
   useId() gives each mount its own name.

   ── what the goo can and cannot do ──────────────────────
   This is feGaussianBlur + feColorMatrix, not a shader. The
   matrix multiplies alpha by `cut` and subtracts half of it,
   so alpha below 0.5 lands on zero and everything above 0.536
   is fully opaque. That hard edge is what fuses nearby shapes
   into one blob — and it is also why this must never touch
   text: glyph antialiasing lives entirely below 0.5, so type
   under this filter loses its edges and then itself. */

function createGooFilter(blur = 3, cut = 24) {
  const id = "goo-" + Math.random().toString(36).slice(2, 9) + Date.now().toString(36).slice(-4);
  const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
  svg.setAttribute("class", "liq-defs");
  svg.setAttribute("aria-hidden", "true");
  svg.setAttribute("focusable", "false");

  const defs = document.createElementNS("http://www.w3.org/2000/svg", "defs");
  const filter = document.createElementNS("http://www.w3.org/2000/svg", "filter");
  filter.setAttribute("id", id);
  filter.setAttribute("x", "-50%");
  filter.setAttribute("y", "-50%");
  filter.setAttribute("width", "200%");
  filter.setAttribute("height", "200%");
  filter.setAttribute("color-interpolation-filters", "sRGB");

  const feGaussianBlur = document.createElementNS("http://www.w3.org/2000/svg", "feGaussianBlur");
  feGaussianBlur.setAttribute("in", "SourceGraphic");
  feGaussianBlur.setAttribute("stdDeviation", String(blur));
  feGaussianBlur.setAttribute("result", "smear");

  const feColorMatrix = document.createElementNS("http://www.w3.org/2000/svg", "feColorMatrix");
  feColorMatrix.setAttribute("in", "smear");
  feColorMatrix.setAttribute("type", "matrix");
  feColorMatrix.setAttribute("values", `1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 ${cut} ${-(cut / 2)}`);

  filter.appendChild(feGaussianBlur);
  filter.appendChild(feColorMatrix);
  defs.appendChild(filter);
  svg.appendChild(defs);

  return { id, url: `url(#${id})`, svg };
}

/* ── inlined from lab/spring ──────────────────────── */
/* ── one spring, for everything that settles ───────────────
   The maths was already on this bench twice, copied by hand:
   Humidity's wheel and Brightness's column both accumulate
   velocity toward a target, damp it, and snap when both the
   delta and the velocity fall under 0.02. Two copies is a
   coincidence; five would be a policy, so it comes out here
   before the elastic blocks are written against it.

   The two shipped copies are deliberately NOT refactored onto
   this. They work, they are tuned, and rewriting the innards
   of two live components to prove a point about duplication
   is how a good afternoon becomes a bad one. This is the one
   new code uses.

   Frames, not milliseconds. `dt` is expressed in sixtieths of
   a second and the damping is RAISED to it rather than
   multiplied by it, so a dropped frame decays the same amount
   of energy as the two frames it replaced. Multiplying is the
   version that makes a spring behave differently on a busy
   page, which is the hardest kind of bug to see.

   The loop parks itself the moment the value has settled.
   CLAUDE.md is not complimentary about the one permanent
   requestAnimationFrame already on this bench and there is no
   case for five more. */

/* Read once, the way the wheel and the pill nav do. A
   preference, not a live input. */
const stillness = () =>
  typeof window !== "undefined" &&
  !!window.matchMedia?.("(prefers-reduced-motion: reduce)").matches;

/* how long a cell takes to swell and settle when a digit lands */
const POP_MS = 260;
const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
const mix = (a, b, t) => a + (b - a) * t;
const smooth = (a, b, v) => {
  const u = clamp((v - a) / (b - a), 0, 1);
  return u * u * (3 - 2 * u);
};

/* a cell, and the air between two */
const W = 36;
const H = 44;
const G = 7;
/* the capsule a right code becomes */
const CAP = 140;

/* ── the merge is a SPRING, not a tween ─────────────────
   It was a 560ms ease-in-out, which arrived and stopped. A
   spring a little under critical lets the capsule run past its
   size and come back — stretching long and thin on the way out
   and settling round — which is what makes the fuse read as
   liquid rather than as boxes sliding. Per frame at 60Hz, from
   a stiffness and a damping ratio, as spring.ts does. */
const MERGE = (() => {
  const k = 0.075;
  const zeta = 0.42;
  return { k, d: 1 - 2 * zeta * Math.sqrt(k) };
})();
const SHAKE_MS = 620;

/**
 * Factory for OneTimeCode in Vanilla JS
 * 
 * @param {HTMLElement|string} mountElement - Target container
 * @param {Object} options
 * @param {number} [options.length=6] - 4 or 6 digits
 * @param {number} [options.corner=12] - Cell corner radius in px
 * @param {Function} [options.onComplete] - Callback on code completion: (code, { accept, reject, reset }) => void
 * @param {Function} [options.onReset] - Callback on code reset
 */
function createOneTimeCode(mountElement, options = {}) {
  const container = typeof mountElement === "string" ? document.querySelector(mountElement) : mountElement;
  if (!container) {
    console.error("OneTimeCode: mount element not found");
    return null;
  }

  const length = options.length === 4 ? 4 : 6;
  const n = length;
  const corner = options.corner !== undefined ? options.corner : 12;
  const r = clamp(corner, 0, H / 2);
  const rowW = n * W + (n - 1) * G;
  const still = stillness();

  let code = "";
  let phase = "type"; // "type" | "check" | "ok" | "no"
  let focus = false;

  /* moving parts, read by the loop */
  const caret = { x: W / 2, v: 0 };
  /* when each cell last took a digit — for the little swell */
  const popAt = [];
  const merge = { m: 0, v: 0, to: 0 };
  let shook = 0;
  let timers = [];
  let raf = 0;

  const later = (fn, ms) => {
    const tid = window.setTimeout(fn, ms);
    timers.push(tid);
    return tid;
  };

  const clearTimers = () => {
    timers.forEach(window.clearTimeout);
    timers = [];
  };

  // Build Goo Filter
  const { url, svg: gooSvg } = createGooFilter(3, 24);

  // Build Root DOM
  const root = document.createElement("div");
  root.className = "otp";
  root.setAttribute("data-phase", phase);

  const row = document.createElement("div");
  row.className = "otp-row";
  row.style.width = `${rowW}px`;
  row.style.height = `${H}px`;

  // Goo layer: cells and capsule
  const goo = document.createElement("div");
  goo.className = "otp-goo";
  goo.setAttribute("aria-hidden", "true");
  if (!still) {
    goo.style.filter = url;
  }

  const cellEls = [];
  for (let i = 0; i < n; i++) {
    const cell = document.createElement("span");
    cell.className = "otp-cell";
    cell.style.width = `${W}px`;
    cell.style.borderRadius = `${r}px`;
    cell.style.transform = `translateX(${(i * (W + G)).toFixed(2)}px)`;
    goo.appendChild(cell);
    cellEls.push(cell);
  }

  const capEl = document.createElement("span");
  capEl.className = "otp-cell";
  capEl.style.display = "none";
  goo.appendChild(capEl);

  row.appendChild(goo);

  // Digits slots
  const slotEls = [];
  const digitEls = [];
  for (let i = 0; i < n; i++) {
    const slot = document.createElement("span");
    slot.className = "otp-slot";
    slot.style.width = `${W}px`;
    slot.style.transform = `translateX(${(i * (W + G)).toFixed(2)}px)`;

    const digit = document.createElement("span");
    digit.className = "otp-digit";
    digit.style.display = "none";

    slot.appendChild(digit);
    row.appendChild(slot);

    slotEls.push(slot);
    digitEls.push(digit);
  }

  // Caret
  const caretEl = document.createElement("i");
  caretEl.className = "otp-caret";
  caretEl.setAttribute("data-on", "false");
  caretEl.style.transform = `translateX(${(W / 2 - 1).toFixed(2)}px) scaleX(1)`;
  row.appendChild(caretEl);

  // Ok capsule message (Verified) with inline SVG checkmark replacing Lucide
  const okEl = document.createElement("span");
  okEl.className = "otp-ok";
  okEl.style.opacity = "0";
  okEl.style.transform = "scale(0.92)";
  okEl.innerHTML = `
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <polyline points="20 6 9 17 4 12"></polyline>
    </svg>
    <span>Verificado</span>
  `;
  row.appendChild(okEl);

  // Hidden overlay input
  const input = document.createElement("input");
  input.className = "otp-field";
  input.type = "text";
  input.inputMode = "numeric";
  input.autocomplete = "one-time-code";
  input.setAttribute("data-1p-ignore", "");
  input.setAttribute("data-lpignore", "true");
  input.setAttribute("data-bwignore", "");
  input.setAttribute("data-form-type", "other");
  input.maxLength = n;
  input.setAttribute("aria-label", `${n}-digit code`);
  input.spellcheck = false;
  row.appendChild(input);

  root.appendChild(row);
  root.appendChild(gooSvg);

  container.innerHTML = "";
  container.appendChild(root);

  /* ── the one loop ────────────────────────────────────────
     Runs while something moves and stops itself after, so a
     row nobody is typing into costs nothing. */
  let prev = 0;
  const tick = (t) => {
    const dt = prev ? clamp((t - prev) / 16.67, 0, 2.5) : 1;
    prev = t;
    let busy = false;

    /* the caret, on a spring with a little give */
    const target = Math.min(code.length, n - 1) * (W + G) + W / 2;
    if (still) {
      caret.x = target;
      caret.v = 0;
    } else {
      caret.v += (target - caret.x) * 0.2 * dt;
      caret.v *= Math.pow(0.62, dt);
      caret.x += caret.v * dt;
      if (Math.abs(target - caret.x) < 0.05 && Math.abs(caret.v) < 0.05) {
        caret.x = target;
        caret.v = 0;
      } else {
        busy = true;
      }
    }

    /* the merge, sprung */
    if (merge.m !== merge.to || merge.v !== 0) {
      if (still) {
        merge.m = merge.to;
        merge.v = 0;
      } else {
        merge.v += (merge.to - merge.m) * MERGE.k * dt;
        merge.v *= Math.pow(MERGE.d, dt);
        merge.m += merge.v * dt;
        if (Math.abs(merge.to - merge.m) < 0.0008 && Math.abs(merge.v) < 0.0008) {
          merge.m = merge.to;
          merge.v = 0;
        } else {
          busy = true;
        }
      }
    }

    if (t - shook < SHAKE_MS + 200) busy = true;

    /* a cell still swelling from a digit keeps the loop alive */
    if (popAt.some((at) => t - at < POP_MS)) busy = true;

    renderFrame(t);

    raf = busy ? requestAnimationFrame(tick) : 0;
  };

  const wake = () => {
    if (raf) return;
    prev = 0;
    raf = requestAnimationFrame(tick);
  };

  const fuse = (to) => {
    merge.to = to;
    wake();
  };

  const setPhaseState = (newPhase) => {
    phase = newPhase;
    root.setAttribute("data-phase", phase);
    input.setAttribute(
      "aria-label",
      phase === "ok" ? "Verified. Press to enter a new code" : `${n}-digit code`
    );
  };

  const updateDigitsDOM = () => {
    for (let i = 0; i < n; i++) {
      const d = code[i];
      const digitEl = digitEls[i];
      if (d) {
        digitEl.textContent = d;
        digitEl.style.display = "";
      } else {
        digitEl.textContent = "";
        digitEl.style.display = "none";
      }
    }
  };

  /* ── where everything is this frame ──────────────────────── */
  const renderFrame = (now) => {
    /* the raw spring runs past 0 and 1; the cells' travel is
       clamped to it, and only the capsule is allowed to take the
       overshoot, as a stretch */
    const raw = merge.m;
    const m = clamp(raw, 0, 1);
    const em = m;
    const home = (i) => i * (W + G);
    const mid = (rowW - W) / 2;
    const since = now - shook;
    const shaking = phase === "no" && since < SHAKE_MS && !still;
    const shake = (i) =>
      shaking ? 5 * Math.exp(-since / 190) * Math.sin((since / 1000) * Math.PI * 2 * 10 + i * 0.55) : 0;
    const cellX = (i) => mix(home(i), mid, em) + shake(i);

    /* ── a digit arriving ─────────────────────────────────────
       The cell that takes it swells a touch and settles: up and
       back on one half-sine, four per cent at the top. Small on
       purpose — it is an acknowledgement, not an event. */
    const pop = (i) => {
      const at = popAt[i];
      if (at === undefined) return 1;
      const u = (now - at) / POP_MS;
      return u > 0 && u < 1 ? 1 + 0.04 * Math.sin(Math.PI * u) : 1;
    };

    /* checking: a soft wave of breath through the cells */
    const breath = (i) => {
      if (phase !== "check" || still) return pop(i);
      const u = ((now % 900) / 900) * (n + 2) - i;
      return 1 - 0.07 * (u > 0 && u < 2 ? Math.sin((u / 2) * Math.PI) : 0);
    };

    const over = raw - 1;
    /* long and thin past its size, round again as it settles:
       width and height trade, so it reads as one soft thing */
    const capW = CAP * smooth(0.3, 1, m) * (1 + Math.max(-0.12, over) * 0.9);
    const capSy = 1 - Math.max(-0.12, over) * 0.55;
    /* cells round off into the capsule's pill as they close in,
       so the two shapes agree by the time the goo joins them */
    const cellR = mix(r, H / 2, smooth(0.15, 0.7, m));
    const typing = phase === "type" && focus;

    // Cells
    for (let i = 0; i < n; i++) {
      cellEls[i].style.borderRadius = `${cellR}px`;
      cellEls[i].style.transform = `translateX(${cellX(i).toFixed(2)}px) scale(${breath(i).toFixed(3)})`;
    }

    // Capsule
    if (capW > 0.5) {
      capEl.style.display = "";
      capEl.style.width = `${capW}px`;
      capEl.style.borderRadius = `${H / 2}px`;
      capEl.style.transform = `translateX(${((rowW - capW) / 2).toFixed(2)}px) scaleY(${capSy.toFixed(3)})`;
    } else {
      capEl.style.display = "none";
    }

    // Slots & Digits
    for (let i = 0; i < n; i++) {
      const x = cellX(i);
      /* a wrong code drains right to left */
      const out = phase === "no" && !still ? smooth(0, 1, (since - 300 - (n - 1 - i) * 45) / 220) : 0;
      slotEls[i].style.transform = `translateX(${x.toFixed(2)}px)`;
      slotEls[i].style.opacity = (1 - smooth(0, 0.45, m)).toFixed(3);

      const dEl = digitEls[i];
      if (dEl && code[i]) {
        dEl.style.transform = `translateY(${(out * 8).toFixed(2)}px)`;
        dEl.style.opacity = (1 - out).toFixed(3);
      }
    }

    // Caret
    const caretOn = typing && code.length < n && m < 0.08;
    caretEl.setAttribute("data-on", caretOn ? "true" : "false");
    caretEl.style.transform = `translateX(${(caret.x - 1).toFixed(2)}px) scaleX(${(1 + Math.min(5, Math.abs(caret.v) * 0.9)).toFixed(3)})`;

    // Ok text
    okEl.style.opacity = smooth(0.72, 1, m).toFixed(3);
    okEl.style.transform = `scale(${mix(0.92, 1, smooth(0.72, 1, m)).toFixed(3)})`;
  };

  /* pressing the capsule splits it back into empty cells */
  const reopen = () => {
    if (phase !== "ok") return;
    code = "";
    input.value = "";
    setPhaseState("type");
    fuse(0);
    updateDigitsDOM();
    input.focus();
    if (typeof options.onReset === "function") {
      options.onReset();
    }
  };

  const onChange = (raw) => {
    if (phase !== "type") return;
    const next = raw.replace(/\D/g, "").slice(0, n);
    /* each new digit swells its cell — a paste runs along the row */
    if (!still && next.length > code.length) {
      const t0 = performance.now();
      for (let i = code.length; i < next.length; i++) popAt[i] = t0 + (i - code.length) * 45;
      wake();
    }
    code = next;
    input.value = code;
    updateDigitsDOM();
    wake();

    if (next.length < n) return;

    /* full: a beat of checking, then the answer */
    setPhaseState("check");
    wake();

    later(() => {
      const api = {
        accept: () => {
          setPhaseState("ok");
          fuse(1);
          input.blur();
        },
        reject: () => {
          setPhaseState("no");
          shook = performance.now();
          wake();
          later(() => {
            code = "";
            input.value = "";
            setPhaseState("type");
            updateDigitsDOM();
            wake();
            input.focus();
          }, SHAKE_MS + n * 45 + 160);
        },
        reset: () => {
          code = "";
          input.value = "";
          setPhaseState("type");
          fuse(0);
          updateDigitsDOM();
          wake();
          input.focus();
        }
      };

      if (typeof options.onComplete === "function") {
        options.onComplete(code, api);
      } else {
        api.accept();
      }
    }, still ? 0 : 420);
  };

  // Event Listeners
  input.addEventListener("input", (e) => onChange(e.target.value));
  input.addEventListener("focus", () => {
    focus = true;
    wake();
  });
  input.addEventListener("blur", () => {
    focus = false;
    wake();
  });
  input.addEventListener("pointerdown", (e) => {
    if (phase === "ok") {
      e.preventDefault();
      reopen();
    }
  });

  // Initial render
  updateDigitsDOM();
  wake();

  return {
    root,
    input,
    getValue: () => code,
    setValue: (val) => onChange(String(val || "")),
    focus: () => input.focus(),
    reset: () => {
      clearTimers();
      code = "";
      input.value = "";
      setPhaseState("type");
      fuse(0);
      updateDigitsDOM();
      wake();
    },
    accept: () => {
      setPhaseState("ok");
      fuse(1);
      input.blur();
    },
    reject: () => {
      setPhaseState("no");
      shook = performance.now();
      wake();
      later(() => {
        code = "";
        input.value = "";
        setPhaseState("type");
        updateDigitsDOM();
        wake();
        input.focus();
      }, SHAKE_MS + n * 45 + 160);
    },
    destroy: () => {
      cancelAnimationFrame(raf);
      clearTimers();
      container.innerHTML = "";
    }
  };
}

// Global export
window.OneTimeCode = createOneTimeCode;

