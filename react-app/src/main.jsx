import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom/client';
import { Toaster, sileo } from 'sileo';
import 'sileo/styles.css';

const STYLE_INJECTION = `
  @keyframes sileoOverlayFadeIn {
    from { opacity: 0; backdrop-filter: blur(0px); -webkit-backdrop-filter: blur(0px); }
    to { opacity: 1; backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
  }
  @keyframes sileoOverlayFadeOut {
    from { opacity: 1; backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
    to { opacity: 0; backdrop-filter: blur(0px); -webkit-backdrop-filter: blur(0px); }
  }
  @keyframes sileoCardEnter {
    from { transform: scale(0.92); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
  }
  @keyframes sileoCardExit {
    from { transform: scale(1); opacity: 1; }
    to { transform: scale(0.92); opacity: 0; }
  }
  @keyframes sileoIconPop {
    0% { transform: scale(0.5) rotate(-15deg); opacity: 0; }
    70% { transform: scale(1.15) rotate(5deg); }
    100% { transform: scale(1) rotate(0deg); opacity: 1; }
  }
  @keyframes sileoPulseGlow {
    0%, 100% { box-shadow: 0 20px 50px -12px rgba(0,0,0,0.8), 0 0 15px rgba(33, 184, 189, 0.15), inset 0 1px 0 rgba(255,255,255,0.05); }
    50% { box-shadow: 0 20px 50px -12px rgba(0,0,0,0.8), 0 0 35px rgba(33, 184, 189, 0.35), inset 0 1px 0 rgba(255,255,255,0.08); }
  }
  @keyframes sileoPulseGlowDanger {
    0%, 100% { box-shadow: 0 20px 50px -12px rgba(0,0,0,0.8), 0 0 15px rgba(239, 68, 68, 0.15), inset 0 1px 0 rgba(255,255,255,0.05); }
    50% { box-shadow: 0 20px 50px -12px rgba(0,0,0,0.8), 0 0 35px rgba(239, 68, 68, 0.35), inset 0 1px 0 rgba(255,255,255,0.08); }
  }
  .sileo-btn-confirm {
    background: linear-gradient(135deg, #21b8bd 0%, #18989c 100%);
    box-shadow: 0 4px 15px rgba(33, 184, 189, 0.25);
  }
  .sileo-btn-confirm:hover {
    background: linear-gradient(135deg, #28cfd4 0%, #1ca9ad 100%) !important;
    box-shadow: 0 6px 20px rgba(33, 184, 189, 0.4) !important;
    transform: translateY(-1px);
  }
  .sileo-btn-destructive {
    background: linear-gradient(135deg, #EF4444 0%, #C22727 100%);
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.25);
  }
  .sileo-btn-destructive:hover {
    background: linear-gradient(135deg, #f05a5a 0%, #d43131 100%) !important;
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4) !important;
    transform: translateY(-1px);
  }
  .sileo-btn-cancel {
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    background: rgba(255, 255, 255, 0.02) !important;
  }
  .sileo-btn-cancel:hover {
    background: rgba(255, 255, 255, 0.08) !important;
    border-color: rgba(255, 255, 255, 0.15) !important;
    color: #FAFAFA !important;
  }
`;

function App() {
  const [confirmModal, setConfirmModal] = useState({
    isOpen: false,
    isClosing: false,
    title: '',
    message: '',
    confirmText: 'Confirmar',
    cancelText: 'Cancelar',
    isDestructive: false,
    resolve: null
  });

  const [successModal, setSuccessModal] = useState({
    isOpen: false,
    isClosing: false,
    idVenta: null
  });

  useEffect(() => {
    // Inyectar animaciones y estilos globales
    const styleTag = document.createElement('style');
    styleTag.innerHTML = STYLE_INJECTION;
    document.head.appendChild(styleTag);

    // Exponer la función de confirmación globalmente con animación de salida
    window.showConfirmModal = (title, message, confirmText = 'Confirmar', cancelText = 'Cancelar', isDestructive = false) => {
      return new Promise((resolve) => {
        setConfirmModal({
          isOpen: true,
          isClosing: false,
          title,
          message,
          confirmText,
          cancelText,
          isDestructive,
          resolve
        });
      });
    };

    // Exponer la función de éxito globalmente
    window.showSuccessModal = (idVenta) => {
      setSuccessModal({
        isOpen: true,
        isClosing: false,
        idVenta
      });
    };

    // Sobrescribir la función de toast del sistema global
    window.showToast = (message, type = 'info') => {
      const normalizedType = type === 'danger' ? 'error' : type;
      const sileoMethod = typeof sileo[normalizedType] === 'function' ? normalizedType : 'info';
      
      sileo[sileoMethod]({
        title: message,
      });
    };

    // Exponer sileo nativo
    window.sileo = sileo;

    return () => {
      document.head.removeChild(styleTag);
    };
  }, []);

  const handleConfirmClose = (value) => {
    // Iniciar animación de salida
    setConfirmModal(prev => ({ ...prev, isClosing: true }));
    setTimeout(() => {
      if (confirmModal.resolve) {
        confirmModal.resolve(value);
      }
      setConfirmModal(prev => ({ ...prev, isOpen: false, isClosing: false }));
    }, 250); // Mismo tiempo que sileoOverlayFadeOut
  };

  const handleSuccessClose = () => {
    setSuccessModal(prev => ({ ...prev, isClosing: true }));
    setTimeout(() => {
      setSuccessModal(prev => ({ ...prev, isOpen: false, isClosing: false }));
    }, 250);
  };

  return (
    <>
      <Toaster 
        position="top-center"
        theme="dark"
        richColors={true}
      />

      {/* Modal de Confirmación React (Sileo-styled) */}
      {confirmModal.isOpen && (
        <div style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          backgroundColor: 'rgba(7, 7, 8, 0.75)',
          zIndex: 9999,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          fontFamily: 'Inter, sans-serif',
          animation: confirmModal.isClosing ? 'sileoOverlayFadeOut 0.25s forwards' : 'sileoOverlayFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards',
        }}>
          <div style={{
            backgroundColor: 'rgba(18, 18, 20, 0.75)',
            backdropFilter: 'blur(20px)',
            webkitBackdropFilter: 'blur(20px)',
            border: '1px solid rgba(255, 255, 255, 0.08)',
            borderRadius: '1.5rem',
            padding: '2rem',
            width: '90%',
            maxWidth: '24rem',
            position: 'relative',
            overflow: 'hidden',
            animation: confirmModal.isClosing 
              ? 'sileoCardExit 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards' 
              : `sileoCardEnter 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards, ${confirmModal.isDestructive ? 'sileoPulseGlowDanger' : 'sileoPulseGlow'} 5s infinite alternate`,
          }}>
            {/* Indicador de barra de color superior al estilo Premium */}
            <div style={{
              position: 'absolute',
              top: 0,
              left: 0,
              right: 0,
              height: '4px',
              background: confirmModal.isDestructive 
                ? 'linear-gradient(90deg, #EF4444, #C22727)' 
                : 'linear-gradient(90deg, #21b8bd, #E04FEE)',
            }} />

            <div style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              textAlign: 'center',
              marginBottom: '1.75rem'
            }}>
              <div style={{
                width: '3.5rem',
                height: '3.5rem',
                borderRadius: '9999px',
                backgroundColor: confirmModal.isDestructive ? 'rgba(239, 68, 68, 0.12)' : 'rgba(33, 184, 189, 0.12)',
                color: confirmModal.isDestructive ? '#ff6560' : '#21b8bd',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                marginBottom: '1.25rem',
                border: confirmModal.isDestructive ? '1px solid rgba(239, 68, 68, 0.2)' : '1px solid rgba(33, 184, 189, 0.2)',
                animation: 'sileoIconPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards'
              }}>
                <span className="material-symbols-outlined text-[30px] font-bold" style={{ fontSize: '30px' }}>
                  {confirmModal.isDestructive ? 'warning' : 'help'}
                </span>
              </div>
              <h3 style={{
                fontSize: '1.35rem',
                fontWeight: '700',
                fontFamily: 'Outfit, sans-serif',
                color: '#FAFAFA',
                marginBottom: '0.625rem',
                margin: 0,
                letterSpacing: '-0.02em'
              }}>{confirmModal.title}</h3>
              <p style={{
                fontSize: '0.9rem',
                color: '#A1A1AA',
                lineHeight: '1.6',
                margin: 0,
                marginTop: '0.25rem'
              }} dangerouslySetInnerHTML={{ __html: confirmModal.message }}></p>
            </div>
            <div style={{ display: 'flex', gap: '0.75rem', width: '100%' }}>
              <button 
                type="button"
                onClick={() => handleConfirmClose(false)}
                className="sileo-btn-cancel"
                style={{
                  flex: 1,
                  paddingTop: '0.75rem',
                  paddingBottom: '0.75rem',
                  fontSize: '0.875rem',
                  fontWeight: '600',
                  color: '#A1A1AA',
                  borderRadius: '0.875rem',
                  cursor: 'pointer',
                  transition: 'all 0.2s cubic-bezier(0.16, 1, 0.3, 1)'
                }}
              >
                {confirmModal.cancelText}
              </button>
              <button 
                type="button"
                onClick={() => handleConfirmClose(true)}
                className={confirmModal.isDestructive ? 'sileo-btn-destructive' : 'sileo-btn-confirm'}
                style={{
                  flex: 1,
                  paddingTop: '0.75rem',
                  paddingBottom: '0.75rem',
                  fontSize: '0.875rem',
                  fontWeight: '600',
                  color: confirmModal.isDestructive ? '#FFFFFF' : '#0A0A0B',
                  border: 'none',
                  borderRadius: '0.875rem',
                  cursor: 'pointer',
                  transition: 'all 0.2s cubic-bezier(0.16, 1, 0.3, 1)'
                }}
              >
                {confirmModal.confirmText}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal de Éxito React */}
      {successModal.isOpen && (
        <div style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          backgroundColor: 'rgba(7, 7, 8, 0.75)',
          zIndex: 9999,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          fontFamily: 'Inter, sans-serif',
          animation: successModal.isClosing ? 'sileoOverlayFadeOut 0.25s forwards' : 'sileoOverlayFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards',
        }}>
          <div style={{
            backgroundColor: 'rgba(18, 18, 20, 0.75)',
            backdropFilter: 'blur(20px)',
            webkitBackdropFilter: 'blur(20px)',
            border: '1px solid rgba(255, 255, 255, 0.08)',
            borderRadius: '1.5rem',
            padding: '2.25rem',
            width: '90%',
            maxWidth: '24rem',
            textAlign: 'center',
            position: 'relative',
            overflow: 'hidden',
            animation: successModal.isClosing 
              ? 'sileoCardExit 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards' 
              : 'sileoCardEnter 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards, sileoPulseGlow 5s infinite alternate',
          }}>
            <div style={{
              position: 'absolute',
              top: 0,
              left: 0,
              right: 0,
              height: '4px',
              background: 'linear-gradient(90deg, #21b8bd, #E04FEE)',
            }} />

            <div style={{
              width: '4rem',
              height: '4rem',
              borderRadius: '9999px',
              backgroundColor: 'rgba(33, 184, 189, 0.12)',
              color: '#21b8bd',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              marginLeft: 'auto',
              marginRight: 'auto',
              marginBottom: '1.25rem',
              border: '1px solid rgba(33, 184, 189, 0.2)',
              animation: 'sileoIconPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards'
            }}>
              <span className="material-symbols-outlined text-4xl" style={{ fontSize: '32px' }}>check_circle</span>
            </div>
            <h3 style={{
              fontSize: '1.35rem',
              fontWeight: '700',
              fontFamily: 'Outfit, sans-serif',
              color: '#FAFAFA',
              marginBottom: '0.625rem',
              margin: 0,
              letterSpacing: '-0.02em'
            }}>¡Venta Exitosa!</h3>
            <p style={{
              fontSize: '0.9rem',
              color: '#A1A1AA',
              marginBottom: '1.75rem',
              margin: 0,
              lineHeight: '1.6'
            }}>La transacción fue registrada correctamente en el sistema Nokia.</p>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
              <a href={`invoice.php?id=${successModal.idVenta}`} target="_blank" rel="noopener noreferrer"
                 className="sileo-btn-confirm"
                 style={{
                   width: '100%',
                   boxSizing: 'border-box',
                   color: '#0A0A0B',
                   fontWeight: '700',
                   paddingTop: '0.85rem',
                   paddingBottom: '0.85rem',
                   borderRadius: '0.875rem',
                   display: 'flex',
                   alignItems: 'center',
                   justifyContent: 'center',
                   gap: '0.5rem',
                   textDecoration: 'none',
                   transition: 'all 0.2s cubic-bezier(0.16, 1, 0.3, 1)',
                   fontSize: '0.875rem'
                 }}
              >
                <span className="material-symbols-outlined">print</span> Imprimir Factura
              </a>
              <button 
                type="button"
                onClick={handleSuccessClose}
                className="sileo-btn-cancel"
                style={{
                  width: '100%',
                  fontWeight: '600',
                  paddingTop: '0.85rem',
                  paddingBottom: '0.85rem',
                  borderRadius: '0.875rem',
                  cursor: 'pointer',
                  transition: 'all 0.2s cubic-bezier(0.16, 1, 0.3, 1)',
                  fontSize: '0.875rem'
                }}
              >
                Nueva Venta
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}

// Iniciar React
const container = document.getElementById('sileo-toaster-container') || (() => {
  const c = document.createElement('div');
  c.id = 'sileo-toaster-container';
  document.body.appendChild(c);
  return c;
})();

ReactDOM.createRoot(container).render(<App />);
