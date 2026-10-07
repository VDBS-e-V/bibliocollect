    <style>
        /* Etikettenbogen 70 × 36 mm, 3 × 8 = 24 Stück, randlos nach links und rechts (Avery Zweckform 3490 oder gleichwertig). */
        :root { --label-x: 0mm; --label-y: 0mm; }
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #000; background: #fff; }
        .bar { padding: 8px 12px; background: #eee; font-size: 14px; }
        .bar button { padding: 4px 12px; margin-right: 12px; }
        .bar label { margin-left: 12px; }
        .bar input[type=number] { width: 4.5em; }
        .sheet { width: 210mm; height: 297mm; padding: calc(4.5mm + var(--label-y)) 0 0 var(--label-x); display: grid; grid-template-columns: repeat(3, 70mm); grid-auto-rows: 36mm; break-after: page; }
        .sheet:last-child { break-after: auto; }
        .label { padding: 2mm 3mm; display: grid; grid-template-rows: auto auto auto 1fr; gap: .5mm; overflow: hidden; background: #fff; }
        .label svg { width: 100%; height: 13mm; display: block; }
        .label .code { font: bold 11pt/1.1 'Courier New', monospace; text-align: center; letter-spacing: .6px; }
        .label .sig { font: bold 10pt/1.1 Arial, sans-serif; text-align: center; }
        .label .title { font: 7pt/1.15 Arial, sans-serif; text-align: center; overflow: hidden; }
        .label .owner { font: 7pt/1.1 Arial, sans-serif; text-align: center; color: #333; }
        .empty { visibility: hidden; }
        @media print { .bar { display: none; } .sheet { margin: 0; } }
        @media screen { body { background: #ddd; } .sheet { background: #fff; margin: 12px auto; box-shadow: 0 0 6px rgba(0,0,0,.3); } .label { outline: 1px dashed #ccc; } }
    </style>
