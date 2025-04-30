document.addEventListener('livewire:initialized', () => {
    Livewire.on('open-modal', (data) => {
        const modal = document.createElement('div');
        modal.id = 'sticker-modal';
        modal.innerHTML = `
            <div style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            ">
                <div style="
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    max-width: 90%;
                    max-height: 90%;
                    overflow: auto;
                ">
                    <div class="sticker" style="
                        width: 62mm;
                        padding: 12px;
                        font-family: Arial, sans-serif;
                        font-size: 12px;
                        box-sizing: border-box;
                    ">
                        <div class="nombre" style="
                            font-size: 18px;
                            font-weight: bold;
                            margin-bottom: 6px;
                            text-align: center;
                        ">
                            ${data.kid.first_name} ${data.kid.last_name}
                        </div>
                        
                        <div class="detalle" style="margin-bottom: 4px;">
                            <strong>Edad:</strong> ${data.kid.age} años
                        </div>
                        
                        <div class="detalle" style="margin-bottom: 4px;">
                            <strong>Responsable:</strong> ${data.contact.first_name} ${data.contact.last_name}
                        </div>
                        
                        <div class="detalle" style="margin-bottom: 4px;">
                            <strong>Fecha:</strong> ${new Date().toLocaleDateString('es-MX')}
                        </div>
                        
                        <div class="detalle" style="margin-bottom: 4px;">
                            <strong>Hora:</strong> ${new Date().toLocaleTimeString('es-MX', {hour: '2-digit', minute:'2-digit'})}
                        </div>

                        ${data.kid.allergies && data.kid.allergies.length > 0 ? `
                            <div class="observaciones" style="
                                margin-top: 10px;
                                font-size: 14px;
                                font-weight: bold;
                                color: #b22222;
                                text-align: center;
                            ">
                                ⚠️ Alergias: ${data.kid.allergies.map(a => a.name).join(', ')}
                            </div>
                        ` : ''}
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button onclick="window.print()" style="
                            background: #4f46e5;
                            color: white;
                            padding: 8px 16px;
                            border-radius: 4px;
                            border: none;
                            cursor: pointer;
                        ">
                            Imprimir
                        </button>
                        <button onclick="this.closest('#sticker-modal').remove()" style="
                            background: #6b7280;
                            color: white;
                            padding: 8px 16px;
                            border-radius: 4px;
                            border: none;
                            margin-left: 8px;
                            cursor: pointer;
                        ">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Estilos para la impresión
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                @page {
                    size: 62mm auto;
                    margin: 0;
                }
                body {
                    margin: 0;
                }
                #sticker-modal > div > div:last-child {
                    display: none;
                }
            }
        `;
        document.head.appendChild(style);
    });
}); 