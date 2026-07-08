import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

// ---- Alpine.js ----
window.Alpine = Alpine;
Alpine.start();

// ---- Chart.js ----
window.Chart = Chart;

// ---- FullCalendar factory ----
window.createAvailabilityCalendar = function (elementId, events = [], onDateClick = null) {
    const el = document.getElementById(elementId);
    if (!el) return;

    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        locale: 'id',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: '',
        },
        height: 'auto',
        events: events,
        dateClick: onDateClick,
        editable: true, // Mengizinkan drag-n-drop & resize
        selectable: true,
        eventClick: function (info) {
            // Ketika event/peminjaman diklik, tampilkan detail info
            alert('Detail Peminjaman:\n' +
                  '-------------------------\n' +
                  'Peminjam: ' + (info.event.extendedProps.peminjam || 'Karyawan') + '\n' +
                  'Barang: ' + info.event.title + '\n' +
                  'Status: ' + (info.event.extendedProps.status || 'Aktif') + '\n' +
                  'Mulai: ' + info.event.start.toLocaleDateString('id-ID') + '\n' +
                  'Kembali: ' + (info.event.end ? info.event.end.toLocaleDateString('id-ID') : 'Hari yang sama')
            );
        },
        eventDrop: function (info) {
            alert('Simulasi Drag-n-Drop:\n' +
                  'Jadwal "' + info.event.title + '" berhasil dipindahkan.\n' +
                  'Tanggal Baru: ' + info.event.start.toLocaleDateString('id-ID') + ' s/d ' + 
                  (info.event.end ? info.event.end.toLocaleDateString('id-ID') : 'Selesai')
            );
        },
        eventResize: function (info) {
            alert('Simulasi Resize:\n' +
                  'Durasi "' + info.event.title + '" disesuaikan.\n' +
                  'Kembali Aktual: ' + (info.event.end ? info.event.end.toLocaleDateString('id-ID') : 'Selesai')
            );
        }
    });

    calendar.render();
    return calendar;
};

// ---- QR Scanner (lazy load html5-qrcode) ----
window.startQrScanner = async function (elementId, onSuccess) {
    const { Html5QrcodeScanner } = await import('html5-qrcode');
    const scanner = new Html5QrcodeScanner(elementId, {
        fps: 10,
        qrbox: { width: 250, height: 250 },
    });
    scanner.render(
        (decodedText) => {
            onSuccess(decodedText);
            scanner.clear();
        },
        (error) => { /* silent */ }
    );
    return scanner;
};

// ---- QR Code Generator ----
window.generateQrCode = async function (elementId, text) {
    const QRCode = await import('qrcode');
    const canvas = document.getElementById(elementId);
    if (canvas) {
        QRCode.default.toCanvas(canvas, text, { width: 160, margin: 2 });
    }
};

// ---- Auto-dismiss flash messages ----
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        setTimeout(() => el.remove(), 4000);
    });
});
