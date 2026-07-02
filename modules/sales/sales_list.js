// assets/js/pages/sales_list.js
document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('exportBtn');
    if (!button) return;

    // Rastrea los tweens activos para su cancelación
    let activeTweens = [];
    let isAnimating = false;

    function resetButton() {
        // Detiene todos los tweens de GSAP activos
        activeTweens.forEach(tw => tw.kill());
        activeTweens = [];
        isAnimating = false;

        const countElem = button.querySelector('.progress-percent');
        const arrowPath = button.querySelector('.arrow-path');
        const linePath = button.querySelector('.line-path');
        const circle = button.querySelector('circle');

        button.classList.remove('loading', 'complete');
        button.querySelector('.button-text').innerHTML = '<span class="material-symbols-outlined text-sm">download</span> Exportar';
        countElem.innerHTML = '0%';
        circle.style.strokeDashoffset = 62.8;
        arrowPath.style.strokeDasharray = '';
        arrowPath.style.transform = '';
        linePath.setAttribute('d', 'M2 16 Q12 16 22 16');
    }

    button.addEventListener('click', (e) => {
        e.preventDefault();

        // Si está animando, cancelar
        if (isAnimating) {
            resetButton();
            if (typeof window.showToast === 'function') {
                window.showToast('Exportación cancelada', 'info');
            } else {
                console.log('Exportación cancelada');
            }
            return;
        }

        // Si completó, ignorar hasta que se reinicie solo
        if (button.classList.contains('complete')) return;

        isAnimating = true;
        button.classList.add('loading');
        
        const countElem = button.querySelector('.progress-percent');
        const arrowPath = button.querySelector('.arrow-path');
        const linePath = button.querySelector('.line-path');
        const circle = button.querySelector('circle');
        
        // Proxy reactivo para las propiedades de la animación SVG
        const svgPath = new Proxy({
            f: 0,
            l: 0,
            s: 1,
            y: 22
        }, {
            set(target, key, value) {
                target[key] = value;
                
                if (key === 'y') {
                    linePath.setAttribute('d', `M2 16 Q12 ${value} 22 16`);
                }
                
                if (key === 'f' || key === 'l') {
                    arrowPath.style.strokeDasharray = `${value} 100`;
                }
                
                if (key === 's') {
                    arrowPath.style.transform = `translateY(${svgPath.y - 16}px) scale(${value / 2})`;
                    arrowPath.style.transformOrigin = 'center';
                }
                return true;
            }
        });
        
        // Muevo un poquito la flecha del botón hacia abajo para iniciar la animación
        const tw1 = gsap.to(svgPath, {
            f: 2,
            l: 38,
            duration: 0.2,
            delay: 0.05
        });
        
        // Le meto un rebote elástico a la flecha como si cayera físicamente en el botón
        const tw2 = gsap.to(svgPath, {
            s: 2,
            y: 16,
            duration: 0.5,
            delay: 0.05,
            ease: "elastic.out(1, 0.4)"
        });
        
        // Hago que suba el porcentaje visual de 0% a 100% mientras se llena el círculo de progreso
        const count = { number: 0 };
        const tw3 = gsap.to(count, {
            number: 100,
            duration: 1.5,
            delay: 0.3,
            onUpdate() {
                const currentVal = Math.round(count.number);
                countElem.innerHTML = currentVal + "%";
                
                const offset = 62.8 - (62.8 * currentVal / 100);
                circle.style.strokeDashoffset = offset;
            },
            onComplete() {
                isAnimating = false;
                activeTweens = [];
                button.classList.remove('loading');
                button.classList.add('complete');
                button.querySelector('.button-text').innerHTML = '<span class="material-symbols-outlined text-[1rem]">check</span> ¡Exportado!';
                
                // Desencadenar la descarga física del CSV
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = 'export_sales.php';
                document.body.appendChild(iframe);
                setTimeout(() => iframe.remove(), 1000);
                
                // Restaurar el botón a su estado inicial después de 2.5s
                setTimeout(() => {
                    resetButton();
                }, 2500);
            }
        });

        activeTweens = [tw1, tw2, tw3];
    });
});
