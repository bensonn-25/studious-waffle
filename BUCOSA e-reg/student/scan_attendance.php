<?php
require_once '../includes/db_connect.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../includes/header.php';
?>

<style>
    body {
        background:
            radial-gradient(circle at top left, rgba(255, 106, 0, 0.12), transparent 32%),
            radial-gradient(circle at top right, rgba(16, 185, 129, 0.12), transparent 28%),
            #f4f6fb;
    }

    .scanner-shell {
        max-width: 1120px;
        margin: 0 auto;
    }

    .scanner-stage {
        background: linear-gradient(180deg, rgba(17, 24, 39, 0.98) 0%, rgba(255, 255, 255, 0.98) 100%);
        border: 1px solid rgba(255, 106, 0, 0.14);
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.14);
    }

    .scanner-frame {
        min-height: 560px;
        background:
            linear-gradient(180deg, rgba(15, 23, 42, 0.88) 0%, rgba(2, 6, 23, 0.98) 100%),
            #111827;
        color: #fff;
        position: relative;
    }

    #reader {
        width: 100%;
        min-height: 420px;
    }

    #reader .html5-qrcode-element {
        border-radius: 18px;
    }

    #reader video {
        border-radius: 18px;
        object-fit: cover;
    }

    .scanner-hero {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }

    .scanner-title {
        font-size: clamp(2rem, 3vw, 2.6rem);
        line-height: 1.05;
        letter-spacing: -0.03em;
        margin-bottom: 10px;
    }

    .scanner-note,
    .scanner-copy {
        font-size: 0.95rem;
        color: #6b7280;
    }

    .scanner-copy {
        color: rgba(255, 255, 255, 0.7);
        max-width: 34rem;
    }

    .scanner-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(16, 185, 129, 0.14);
        color: #34d399;
        border: 1px solid rgba(52, 211, 153, 0.25);
        border-radius: 999px;
        padding: 8px 14px;
        font-weight: 700;
        white-space: nowrap;
    }

    .scanner-panel {
        background: rgba(255, 255, 255, 0.98);
    }

    .scan-hint {
        position: absolute;
        inset: auto 24px 24px 24px;
        background: rgba(15, 23, 42, 0.66);
        backdrop-filter: blur(10px);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 18px;
        padding: 14px 16px;
    }

    .scan-glow {
        position: absolute;
        inset: 18px;
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 22px;
        pointer-events: none;
    }

    .scan-glow::before,
    .scan-glow::after {
        content: '';
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        width: 58%;
        height: 2px;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.88), transparent);
        animation: scanLine 2.4s linear infinite;
        opacity: 0.9;
    }

    .scan-glow::before {
        top: 18%;
    }

    .scan-glow::after {
        top: 18%;
        animation-delay: 1.2s;
    }

    @keyframes scanLine {
        0% { transform: translateX(-50%) translateY(0); opacity: 0; }
        15% { opacity: 1; }
        50% { opacity: 1; }
        100% { transform: translateX(-50%) translateY(320px); opacity: 0; }
    }
</style>

<div class="container mt-4 scanner-shell">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="scanner-stage shadow-sm">
                <div class="row g-0">
                    <div class="col-lg-7 scanner-frame p-3 p-md-4 p-lg-5">
                        <div class="scanner-hero">
                            <div>
                                <div class="scanner-badge mb-3">
                                    <i class="fas fa-bolt"></i>
                                    Automatic attendance scan
                                </div>
                                <h2 class="scanner-title mb-0">Scan QR Code</h2>
                                <p class="scanner-copy mt-3 mb-0">Point your camera at the session QR code and the system will open the attendance page immediately after detection.</p>
                            </div>
                            <span class="badge bg-success align-self-start">Live Camera</span>
                        </div>

                        <div class="card border-0 shadow-lg mb-3 overflow-hidden position-relative">
                            <div class="card-body p-0 scanner-camera-shell">
                                <div id="reader"></div>
                                <div class="scan-glow"></div>
                                <div class="scan-hint">
                                    <div class="fw-bold mb-1">Ready to scan</div>
                                    <div class="small text-white-50">Hold the QR code inside the frame. Registration happens automatically on success.</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                            <button type="button" class="btn btn-primary px-4" id="startScannerBtn">
                                <i class="fas fa-camera-retro me-2"></i>Restart Scanner
                            </button>
                            <button type="button" class="btn btn-outline-light px-4" id="stopScannerBtn" disabled>
                                <i class="fas fa-stop me-2"></i>Stop Scanner
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary px-4 bg-white ms-auto">
                                Cancel
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-5 p-4 p-md-5 scanner-panel bg-white">
                        <h4 class="fw-bold mb-3">How it works</h4>
                        <ol class="scanner-note ps-3 mb-4">
                            <li>Allow camera access when prompted.</li>
                            <li>Point the camera at the session QR code.</li>
                            <li>Your attendance is marked automatically after a valid scan.</li>
                        </ol>

                        <div id="scanStatus" class="alert alert-info">
                            Starting scanner...
                        </div>

                        <div class="mb-3">
                            <label for="manualInput" class="form-label fw-bold">Manual fallback</label>
                            <input type="text" id="manualInput" class="form-control" placeholder="Paste a full attendance URL or token here">
                            <div class="form-text">Useful if camera access is blocked on your device.</div>
                        </div>
                        <button type="button" class="btn btn-outline-primary w-100" id="openManualBtn">
                            Open Attendance Link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    const readerId = "reader";
    const statusEl = document.getElementById("scanStatus");
    const startButton = document.getElementById("startScannerBtn");
    const stopButton = document.getElementById("stopScannerBtn");
    const manualInput = document.getElementById("manualInput");
    const openManualButton = document.getElementById("openManualBtn");

    let html5QrCode = null;
    let scannerRunning = false;
    let autoStartAttempted = false;

    function setStatus(message, type = "info") {
        statusEl.className = `alert alert-${type}`;
        statusEl.textContent = message;
    }

    function buildAttendanceUrl(rawValue) {
        const value = (rawValue || "").trim();

        if (!value) {
            return null;
        }

        try {
            const parsedUrl = new URL(value, window.location.origin);
            if (parsedUrl.pathname.includes("mark_attendance.php")) {
                return parsedUrl.href;
            }

            const token = parsedUrl.searchParams.get("token");
            if (token) {
                return new URL(`../attendance/scan.php?token=${encodeURIComponent(token)}`, window.location.href).href;
            }
        } catch (error) {
            // Fall through to token handling below.
        }

        const tokenMatch = value.match(/token=([A-Za-z0-9]+)/i);
        if (tokenMatch && tokenMatch[1]) {
            return new URL(`../attendance/scan.php?token=${encodeURIComponent(tokenMatch[1])}`, window.location.href).href;
        }

        if (/^[A-Fa-f0-9]{16,}$/.test(value)) {
            return new URL(`../attendance/scan.php?token=${encodeURIComponent(value)}`, window.location.href).href;
        }

        return null;
    }

    async function stopScanner() {
        if (html5QrCode) {
            try {
                await html5QrCode.stop();
            } catch (error) {
                // Ignore stop errors when the camera is already idle.
            }

            try {
                await html5QrCode.clear();
            } catch (error) {
                // Ignore clear errors when the scanner view is already removed.
            }
        }

        scannerRunning = false;

        startButton.disabled = false;
        stopButton.disabled = true;
    }

    async function handleScanResult(decodedText) {
        const attendanceUrl = buildAttendanceUrl(decodedText);

        if (!attendanceUrl) {
            setStatus("QR code scanned, but the value is not a valid attendance link.", "warning");
            return;
        }

        setStatus("QR code recognized. Opening attendance page...", "success");
        await stopScanner();
        window.location.replace(attendanceUrl);
    }

    async function startScanner() {
        if (!window.Html5Qrcode) {
            setStatus("Camera scanner library failed to load. Use the manual fallback below.", "danger");
            return;
        }

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode(readerId);
        }

        try {
            startButton.disabled = true;
            setStatus("Starting camera...", "info");

            const cameras = await Html5Qrcode.getCameras();
            if (!cameras || cameras.length === 0) {
                throw new Error("No camera devices were found on this device.");
            }

            const cameraId = cameras.find((camera) => /back|rear|environment/i.test(camera.label))?.id || cameras[0].id;

            await html5QrCode.start(
                { deviceId: { exact: cameraId } },
                {
                    fps: 12,
                    qrbox: { width: 260, height: 260 },
                    aspectRatio: 1.0,
                    disableFlip: false
                },
                async (decodedText) => {
                    if (!scannerRunning) {
                        return;
                    }

                    scannerRunning = false;
                    await handleScanResult(decodedText);
                },
                () => {
                    // Ignore transient scan misses and keep scanning.
                }
            );

            scannerRunning = true;
            stopButton.disabled = false;
            setStatus("Camera is live. Align the QR code inside the frame.", "success");
        } catch (error) {
            scannerRunning = false;
            startButton.disabled = false;
            stopButton.disabled = true;
            setStatus(error.message || "Unable to start the camera scanner.", "danger");
        }
    }

    startButton.addEventListener("click", startScanner);
    stopButton.addEventListener("click", stopScanner);

    openManualButton.addEventListener("click", function() {
        const attendanceUrl = buildAttendanceUrl(manualInput.value);

        if (!attendanceUrl) {
            setStatus("Enter a valid attendance URL or token first.", "warning");
            return;
        }

        window.location.assign(attendanceUrl);
    });

    window.addEventListener("DOMContentLoaded", async () => {
        if (!autoStartAttempted) {
            autoStartAttempted = true;
            await startScanner();
        }
    });

    window.addEventListener("beforeunload", () => {
        if (html5QrCode && scannerRunning) {
            html5QrCode.stop().catch(() => {});
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>
