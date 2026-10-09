// Kamera-Scan: liest Strichcodes (Code 128, EAN) und QR-Codes aus dem Kamerabild.
// Nutzt zuerst den Barcode-Leser des Browsers (Chrome/Android), sonst ZXing (läuft auch in iOS-Safari, ohne WebAssembly).
// Wird nur bei Bedarf nachgeladen (dynamischer Import aus app.js).

const NATIVE_FORMATS = ['code_128', 'code_39', 'ean_13', 'ean_8', 'qr_code'];

/**
 * Startet die Kamera im Videoelement und ruft onCode einmal mit dem ersten gelesenen Code auf.
 * Gibt eine Funktion zurück, die Kamera und Suche beendet.
 */
export async function startScanner(video, onCode) {
    const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: 'environment' } },
        audio: false,
    });

    let stopped = false;
    let controls = null;
    let timer = 0;

    const stop = () => {
        stopped = true;
        window.clearTimeout(timer);
        if (controls) {
            try { controls.stop(); } catch (_) { /* schon beendet */ }
        }
        stream.getTracks().forEach((track) => track.stop());
        video.srcObject = null;
    };

    const found = (text) => {
        if (stopped || !text) return;
        stop();
        onCode(String(text).trim());
    };

    video.srcObject = stream;
    video.setAttribute('playsinline', 'true');
    await video.play();

    let native = null;

    if ('BarcodeDetector' in window) {
        try {
            const supported = await window.BarcodeDetector.getSupportedFormats();
            const formats = NATIVE_FORMATS.filter((format) => supported.includes(format));
            if (formats.length > 0) native = new window.BarcodeDetector({ formats });
        } catch (_) {
            native = null;
        }
    }

    if (native) {
        const tick = async () => {
            if (stopped) return;
            try {
                const codes = await native.detect(video);
                if (codes.length > 0) return found(codes[0].rawValue);
            } catch (_) { /* nächster Versuch */ }
            timer = window.setTimeout(tick, 150);
        };
        tick();

        return stop;
    }

    const { BrowserMultiFormatReader } = await import('@zxing/browser');
    const { BarcodeFormat, DecodeHintType } = await import('@zxing/library');
    const hints = new Map();
    hints.set(DecodeHintType.POSSIBLE_FORMATS, [
        BarcodeFormat.CODE_128,
        BarcodeFormat.CODE_39,
        BarcodeFormat.EAN_13,
        BarcodeFormat.EAN_8,
        BarcodeFormat.QR_CODE,
    ]);
    const reader = new BrowserMultiFormatReader(hints, { delayBetweenScanAttempts: 120 });

    if (stopped) return stop;

    controls = await reader.decodeFromStream(stream, video, (result) => {
        if (result) found(result.getText());
    });

    return stop;
}
