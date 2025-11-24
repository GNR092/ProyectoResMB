<div id="modal-modificar-montos" class="bg-white bg-opacity-95 rounded-lg shadow-2xl max-w-4xl w-full mx-4 sm:mx-auto p-6 fixed hidden z-50">
    <div class="fixed top-20 mx-auto p-5 border  shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-3">
            <h3 class="text-lg font-bold">Modificar Montos de Solicitud</h3>
            <button class="text-gray-400 hover:text-gray-600" @click="RevisionX().cerrarModalModificarMontos(document.getElementById('modificar_id_solicitud').value)">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="mt-2">
            <form id="form-modificar-montos">
                <input type="hidden" id="modificar_id_solicitud" name="id_solicitud">
                <div id="proveedor-select-container" class="mb-4"></div>
                <div id="productos-modificar-container" class="space-y-4">
                    <!-- Productos/Servicios cargados dinámicamente por JS -->
                </div>
                <div class="mt-4">
                    <label for="modificar_iva" class="block text-sm font-medium text-gray-700">Agregar IVA:</label>
                   <input type="checkbox" name="iva" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                </div>
                <div class="mt-4">
                    <label for="modificar_comentarios" class="block text-sm font-medium text-gray-700">Comentarios (opcional)</label>
                    <textarea id="modificar_comentarios" name="comentarios" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                </div>

                <div id="calculos-modificar" class="mt-6 p-4 border-t border-gray-200 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 font-medium">Subtotal:</span>
                        <span id="subtotal-modificar" class="text-gray-900 font-semibold">$0.00</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 font-medium">IVA (16%):</span>
                        <span id="iva-modificar" class="text-gray-900 font-semibold">$0.00</span>
                    </div>
                    <div class="flex justify-between items-center text-lg">
                        <span class="text-gray-800 font-bold">Total:</span>
                        <span id="total-modificar" class="text-blue-600 font-bold">$0.00</span>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-4">
                    <button type="button" @click="RevisionX().cerrarModalModificarMontos(document.getElementById('modificar_id_solicitud').value)" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>