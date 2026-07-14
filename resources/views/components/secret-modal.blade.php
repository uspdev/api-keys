<div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-api-key-modal="secret" style="display: none;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title h5">API Key criada com sucesso</h3>
                <button type="button" class="close" aria-label="Fechar" data-api-key-close>
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <p>Copie esta credencial agora. Ela será exibida apenas uma vez.</p>
                <code id="{{ $componentId }}-secret" class="api-key-secret-value border rounded p-3 mb-3">{{ $token }}</code>
                <div class="alert alert-warning mb-0" role="alert">
                    O segredo não é armazenado em texto puro e não poderá ser recuperado depois que esta janela for fechada.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-api-key-copy="#{{ $componentId }}-secret">Copiar chave</button>
                <button type="button" class="btn btn-primary" data-api-key-close>Concluído</button>
            </div>
        </div>
    </div>
</div>
