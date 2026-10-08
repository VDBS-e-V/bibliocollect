    <style>
        /* Etikettenbogen 70 × 36 mm, 3 × 8 = 24 Stück, randlos nach links und rechts (Avery Zweckform 3490 oder gleichwertig). */
        :root { --label-x: 0mm; --label-y: 0mm; --label-pad-y: 3mm; --label-pad-x: 6mm; }
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #000; background: #fff; }
        .bar { padding: 8px 12px; background: #eee; font-size: 14px; }
        .bar button { padding: 4px 12px; margin-right: 12px; }
        .bar label { margin-left: 12px; }
        .bar input[type=number] { width: 4.5em; }
        .sheet { width: 210mm; height: 297mm; padding: calc(4.5mm + var(--label-y)) 0 0 var(--label-x); display: grid; grid-template-columns: repeat(3, 70mm); grid-auto-rows: 36mm; break-after: page; }
        .sheet:last-child { break-after: auto; }

        /* Aufbau: oben links das Logo, oben rechts der Name, unten mittig der Strichcode und darunter klein die Nummer (für den Notfall ohne Scanner). */
        .label { padding: var(--label-pad-y) var(--label-pad-x) calc(var(--label-pad-y) - .4mm); display: grid; grid-template-rows: 8mm 1fr auto auto; gap: .4mm; overflow: hidden; background: #fff; }
        .label .head { display: flex; align-items: center; justify-content: space-between; }
        .label .logo { height: 8mm; width: auto; display: block; }
        .label .name { font: bold 14pt/1 Arial, Helvetica, sans-serif; color: #58275a; letter-spacing: -.2px; }
        .label .mid { text-align: center; overflow: hidden; align-self: center; width: 100%; }
        .label .sig { font: bold 9pt/1.1 Arial, sans-serif; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .label .title { font: 6.5pt/1.15 Arial, sans-serif; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .label svg { width: 100%; max-width: 56mm; height: 10.5mm; display: block; margin: 0 auto 1.2mm; }
        .label .code { font: bold 8.5pt/1 'Courier New', monospace; color: #000; text-align: center; letter-spacing: 1px; }
        .empty { visibility: hidden; }
        @media print { .bar { display: none; } .sheet { margin: 0; } .label * { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
        @media screen { body { background: #ddd; } .sheet { background: #fff; margin: 12px auto; box-shadow: 0 0 6px rgba(0,0,0,.3); } .label { outline: 1px dashed #ccc; } }
    </style>
