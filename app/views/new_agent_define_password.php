<div class="container-fluid mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-6 col-sm-8 col-10">
            <div class="card p-4">

                <div class="d-flex align-items-center justify-content-center my-4">
                    <img src="assets/images/logo.png" class="img-fluid me-3">
                    <h2><strong><?= APP_NAME ?></strong></h2>
                </div>

                <div class="row justify-content-center">
                    <div class="col-8">

                        <form action="?ct=main&mt=define_password_submit" method="post" novalidate>

                            <input type="hidden" name="purl" value="<?= $purl ?>">
                            <input type="hidden" name="id" value="<?= aes_encrypt($id) ?>">

                            <p class="mb-3">Para concluir o registro, defina sua <strong>SENHA</strong>.</p>

                            <div class="mb-3">
                                <label for="text_new_password" class="form-label">Password</label>
                                <input type="password" name="text_password" id="text_password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="text_repeat_new_password" class="form-label">Repetir password</label>
                                <input type="password" name="text_repeat_new_password" id="text_repeat_new_password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <button type="submit" for="text_repeat_password" class="btn btn-secondary px-3" class="fa-solid"> Enviar</button>
                            </div>
                            <?php if (!empty($validation_errors)):  ?>
                                <div class="alert alert-danger p-2 text-center">
                                    <?= $validation_errors ?>
                                </div>
                            <?php endif; ?>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>