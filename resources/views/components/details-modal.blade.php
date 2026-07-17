<div id="{{ $componentId }}-details" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"
  aria-labelledby="{{ $componentId }}-details-title" data-api-keys-modal="details" style="display: none;">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="{{ $componentId }}-details-title" class="modal-title h5">Detalhes da API Key</h3>
        <button type="button" class="close" aria-label="Fechar" data-api-keys-close>
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <div class="font-weight-bold" data-api-keys-detail="name"></div>
          <code data-api-keys-detail="prefix"></code>
        </div>

        <dl class="row mb-0">
          <dt class="col-sm-5">Status</dt>
          <dd class="col-sm-7"><span class="badge" data-api-keys-detail="status"></span></dd>

          <dt class="col-sm-5">Tipo</dt>
          <dd class="col-sm-7" data-api-keys-detail="purpose"></dd>

          <dt class="col-sm-5">Papel</dt>
          <dd class="col-sm-7" data-api-keys-detail="role"></dd>

          <dt class="col-sm-5">Criada em</dt>
          <dd class="col-sm-7" data-api-keys-detail="created_at"></dd>

          <dt class="col-sm-5">Expiração</dt>
          <dd class="col-sm-7" data-api-keys-detail="expires_at"></dd>

          <dt class="col-sm-5">Último uso</dt>
          <dd class="col-sm-7" data-api-keys-detail="last_used_at"></dd>

          <dt class="col-sm-5">Último IP</dt>
          <dd class="col-sm-7" data-api-keys-detail="last_used_ip"></dd>

          <dt class="col-sm-5">Acessos</dt>
          <dd class="col-sm-7" data-api-keys-detail="access_count"></dd>

          <dt class="col-sm-5" data-api-keys-detail-row="created_by">Criada pelo usuário ID</dt>
          <dd class="col-sm-7" data-api-keys-detail="created_by" data-api-keys-detail-row="created_by"></dd>

          <dt class="col-sm-5" data-api-keys-detail-row="revoked_at">Revogada em</dt>
          <dd class="col-sm-7" data-api-keys-detail="revoked_at" data-api-keys-detail-row="revoked_at"></dd>

          <dt class="col-sm-5" data-api-keys-detail-row="revoked_by">Revogada pelo usuário ID</dt>
          <dd class="col-sm-7" data-api-keys-detail="revoked_by" data-api-keys-detail-row="revoked_by"></dd>
        </dl>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-api-keys-close>Fechar</button>
      </div>
    </div>
  </div>
</div>
