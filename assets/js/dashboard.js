/* Dashboard: grafik tren dan komposisi kategori. */

(function () {
  'use strict';

  const data = window.dashboardData;
  if (!data || typeof Chart === 'undefined') return;

  const tealDark = '#1A535C';
  const orange = '#FF6B35';
  const grid = '#EFEFEF';

  Chart.defaults.font.family = "'Inter', sans-serif";
  Chart.defaults.font.size = 11;
  Chart.defaults.color = '#666666';

  /* ------------------------------------------------ Tren 7 hari */
  const elTren = document.getElementById('chartTren');
  if (elTren) {
    new Chart(elTren, {
      type: 'line',
      data: {
        labels: data.label,
        datasets: [
          {
            label: 'Pendapatan',
            data: data.pendapatan,
            borderColor: tealDark,
            backgroundColor: 'rgba(26, 83, 92, 0.10)',
            borderWidth: 2,
            tension: 0.32,
            fill: true,
            pointRadius: 3,
            pointBackgroundColor: tealDark,
            yAxisID: 'y'
          },
          {
            label: 'Pesanan',
            data: data.pesanan,
            borderColor: orange,
            backgroundColor: orange,
            borderWidth: 2,
            tension: 0.32,
            pointRadius: 3,
            yAxisID: 'y1'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                if (ctx.datasetIndex === 0) {
                  return 'Pendapatan: Rp ' + Number(ctx.parsed.y).toLocaleString('id-ID');
                }
                return 'Pesanan: ' + ctx.parsed.y;
              }
            }
          }
        },
        scales: {
          x: { grid: { display: false }, border: { color: grid } },
          y: {
            position: 'left',
            beginAtZero: true,
            grid: { color: grid },
            border: { display: false },
            ticks: {
              callback: function (v) {
                return v >= 1000 ? (v / 1000) + 'k' : v;
              }
            }
          },
          y1: {
            position: 'right',
            beginAtZero: true,
            grid: { display: false },
            border: { display: false },
            ticks: { precision: 0 }
          }
        }
      }
    });
  }

  /* ------------------------------------------- Komposisi kategori */
  const elKategori = document.getElementById('chartKategori');
  const legendBox = document.getElementById('legendKategori');
  if (elKategori && data.kategori) {
    const labels = Object.keys(data.kategori);
    const values = Object.values(data.kategori);
    const warna = ['#1A535C', '#4ECDC4', '#FF6B35', '#8AB6BC', '#E0A106'];

    new Chart(elKategori, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: warna.slice(0, labels.length),
          borderWidth: 2,
          borderColor: '#FFFFFF'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '64%',
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                const persen = total ? Math.round((ctx.parsed / total) * 100) : 0;
                return ctx.label + ': ' + ctx.parsed + ' item (' + persen + '%)';
              }
            }
          }
        }
      }
    });

    if (legendBox) {
      legendBox.innerHTML = labels.map(function (label, i) {
        return '<span class="legend__item"><span class="legend__swatch" style="background:' +
          warna[i] + '"></span>' + label + ' (' + values[i] + ')</span>';
      }).join('');
    }
  }
})();
