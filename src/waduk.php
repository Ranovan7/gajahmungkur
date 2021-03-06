<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Manage Operasi Bendungan

$app->group('/waduk', function() use ($loggedinMiddleware, $adminAuthorizationMiddleware) {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $bendungan = $this->db->query("SELECT * FROM waduk")->fetchAll();
        $vnotch = $this->db->query("SELECT * FROM vnotch")->fetchAll();
        $piezometer = $this->db->query("SELECT * FROM piezometer")->fetchAll();

        $waduk = [];
        foreach ($bendungan as $b) {
            $waduk[$b['id']] = [
                'waduk' => $b,
                'vnotch' => [],
                'piezometer' => []
            ];
        }
        foreach ($vnotch as $v) {
            $waduk[$v['waduk_id']]['vnotch'][] = $v;
        }
        foreach ($piezometer as $p) {
            $waduk[$p['waduk_id']]['piezometer'][] = $p;
        }
        krsort($waduk);
        return $this->view->render($response, 'waduk/index.html', [
            'waduk' => $waduk
        ]);
    })->setName('waduk');

    $this->group('/add', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'waduk/add.html');
        })->setName('waduk.add');

        $this->post('[/]', function(Request $request, Response $response, $args) {
            $form = $request->getParams();
            // echo $form['username'];
            // echo $form['password'];
            // echo $form['waduk'];
            // echo empty($form['sedimen']) ? 0 : $form['sedimen'];
            $stmt = $this->db->prepare("INSERT INTO waduk
                                    (nama, kab, lbi,
                                        volume, sedimen, elev_puncak,
                                        muka_air_min, muka_air_normal, muka_air_max,
                                        bts_elev_awas, bts_elev_siaga, bts_elev_waspada)
                                    VALUES
                                    (:nama, :kab, :lbi,
                                        :volume, :sedimen, :elev_puncak,
                                        :muka_air_min, :muka_air_normal, :muka_air_max,
                                        :bts_elev_awas, :bts_elev_siaga, :bts_elev_waspada)");
            $stmt->execute([
                ':nama' => $form['nama'],
                ':kab' => $form['kab'],
                ':lbi' => $form['lbi'],
                ':volume' => $form['volume'],
                ':sedimen' => empty($form['sedimen']) ? 0 : $form['sedimen'],
                ':elev_puncak' => empty($form['elev_puncak']) ? 0 : $form['elev_puncak'],
                ':muka_air_min' => empty($form['ma_min']) ? 0 : $form['ma_min'],
                ':muka_air_normal' => empty($form['ma_norm']) ? 0 : $form['ma_norm'],
                ':muka_air_max' => empty($form['ma_max']) ? 0 : $form['ma_max'],
                ':bts_elev_awas' => empty($form['elev_awas']) ? 0 : $form['elev_awas'],
                ':bts_elev_siaga' => empty($form['elev_siaga']) ? 0 : $form['elev_siaga'],
                ':bts_elev_waspada' => empty($form['elev_waspada']) ? 0 : $form['elev_waspada']
            ]);

            // $this->flash->addMessage('messages', 'Berhasil Menambah Waduk');
            return $this->response->withRedirect('/waduk');
        })->setName('waduk.add');

    });

    $this->group('/{id}', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            $id = $request->getAttribute('id');
            $waduk = $this->db->query("SELECT * FROM waduk WHERE id={$id}")->fetch();

            $vnotch = $this->db->query("SELECT * FROM vnotch WHERE waduk_id={$id}")->fetchAll();
            $piezometer = $this->db->query("SELECT * FROM piezometer WHERE waduk_id={$id}")->fetchAll();

            return $this->view->render($response, 'waduk/detail.html', [
                'waduk' => $waduk,
                'vnotch' => $vnotch,
                'piezometer' => $piezometer
            ]);
        })->setName('waduk.detail');

        $this->post('[/]', function(Request $request, Response $response, $args) {
            $id = $request->getAttribute('id');
            $form = $request->getParams();

            $stmt = $this->db->prepare("UPDATE waduk
                                    SET nama=:nama, kab=:kab, lbi=:lbi,
                                        volume=:volume, sedimen=:sedimen, elev_puncak=:elev_puncak,
                                        muka_air_min=:muka_air_min, muka_air_normal=:muka_air_normal, muka_air_max=:muka_air_max,
                                        bts_elev_awas=:bts_elev_awas, bts_elev_siaga=:bts_elev_siaga, bts_elev_waspada=:bts_elev_waspada
                                    WHERE id=:id");
            $stmt->execute([
                ':id' => $id,
                ':nama' => $form['nama'],
                ':kab' => $form['kab'],
                ':lbi' => $form['lbi'],
                ':volume' => $form['volume'],
                ':sedimen' => empty($form['sedimen']) ? 0 : $form['sedimen'],
                ':elev_puncak' => empty($form['elev_puncak']) ? 0 : $form['elev_puncak'],
                ':muka_air_min' => empty($form['ma_min']) ? 0 : $form['ma_min'],
                ':muka_air_normal' => empty($form['ma_norm']) ? 0 : $form['ma_norm'],
                ':muka_air_max' => empty($form['ma_max']) ? 0 : $form['ma_max'],
                ':bts_elev_awas' => empty($form['elev_awas']) ? 0 : $form['elev_awas'],
                ':bts_elev_siaga' => empty($form['elev_siaga']) ? 0 : $form['elev_siaga'],
                ':bts_elev_waspada' => empty($form['elev_waspada']) ? 0 : $form['elev_waspada']
            ]);

            // $this->flash->addMessage('messages', 'Berhasil Mengedit Waduk');
            return $this->response->withRedirect('/waduk');
        })->setName('waduk.detail');

        // update per column
        $this->post('/update', function(Request $request, Response $response, $args) {
            $id = $request->getAttribute('id');
            $form = $request->getParams();

            $column = $form['name'];
            $stmt = $this->db->prepare("UPDATE waduk SET {$column}=:value WHERE id=:id");
            $stmt->execute([
                ':value' => $form['value'],
                ':id' => $form['pk']
            ]);

            return $response->withJson([
                "name" => $form['name'],
                "pk" => $form['pk'],
                "value" => $form['value']
            ], 200);
        })->setName('waduk.update');

        // delete
        $this->get('/del', function(Request $request, Response $response, $args) {
            $id = $request->getAttribute('id');
            $waduk = $user = $this->db->query("SELECT * FROM waduk WHERE id={$id}")->fetch();
            return $this->view->render($response, 'waduk/delete.html', [
                'waduk' => $waduk,
            ]);
        })->setName('waduk.delete');

        $this->post('/del', function(Request $request, Response $response, $args) {
            $id = $request->getAttribute('id');
            $user = $user = $this->db->query("SELECT * FROM waduk WHERE id={$id}")->fetch();
            $stmt = $this->db->prepare("DELETE FROM waduk WHERE id=:id");
            $stmt->execute([
                ':id' => $id
            ]);
            // die("User {$user['username']} dihapus!"); // change to redirect next
            return $this->response->withRedirect('/waduk');
        })->setName('waduk.delete');

        // vnotch
        $this->group('/vnotch', function() {

            $this->get('/add', function(Request $request, Response $response, $args) {
                $id = $request->getAttribute('id');
                $vnotch_id = $request->getAttribute('vnotch_id');
                $waduk = $this->db->query("SELECT * FROM waduk WHERE id={$id}")->fetch();

                return $this->view->render($response, 'waduk/add/vnotch.html', [
                    'waduk' => $waduk
                ]);
            })->setName('waduk.vnotch.add');

            $this->post('/add', function(Request $request, Response $response, $args) {
                $id = $request->getAttribute('id');
                // $waduk = $this->db->query("SELECT * FROM waduk WHERE id={$id}")->fetch();

                $form = $request->getParams();

                $stmt = $this->db->prepare("INSERT INTO vnotch
                                        (nama, panjang_saluran, bts_rembesan, waduk_id)
                                        VALUES
                                        (:nama, :panjang_saluran, :bts_rembesan, :waduk_id)");
                $stmt->execute([
                    ':nama' => $form['nama'],
                    ':waduk_id' => $id,
                    ':panjang_saluran' => empty($form['panjang_saluran']) ? 0 : $form['panjang_saluran'],
                    ':bts_rembesan' => empty($form['bts_rembesan']) ? 0 : $form['bts_rembesan'],
                ]);

                // $this->flash->addMessage('messages', 'VNotch Berhasil Ditambahkan');
                return $this->response->withRedirect($this->router->pathFor('waduk.detail', ['id' => $id]));
            })->setName('waduk.vnotch.add');

            $this->group('/{vnotch_id}', function() {

                $this->get('/del', function(Request $request, Response $response, $args) {
                    $id = $request->getAttribute('id');
                    $vnotch_id = $request->getAttribute('vnotch_id');
                    $user = $user = $this->db->query("SELECT * FROM vnotch WHERE id={$vnotch_id}")->fetch();
                    $stmt = $this->db->prepare("DELETE FROM vnotch WHERE id=:id");
                    $stmt->execute([
                        ':id' => $vnotch_id
                    ]);

                    // $this->flash->addMessage('messages', 'VNotch Berhasil Dihapus');
                    return $this->response->withRedirect($this->router->pathFor('waduk.detail', ['id' => $id]));
                })->setName('waduk.vnotch.delete');
            });
        });

        $this->group('/piezometer', function() {

            $this->get('/add', function(Request $request, Response $response, $args) {
                $id = $request->getAttribute('id');
                $waduk = $this->db->query("SELECT * FROM waduk WHERE id={$id}")->fetch();

                return $this->view->render($response, 'waduk/add/piezometer.html', [
                    'waduk' => $waduk
                ]);
            })->setName('waduk.piezometer.add');

            $this->post('/add', function(Request $request, Response $response, $args) {
                $id = $request->getAttribute('id');
                $waduk = $this->db->query("SELECT * FROM waduk WHERE id={$id}")->fetch();

                $form = $request->getParams();

                $stmt = $this->db->prepare("INSERT INTO piezometer
                                        (nama, elev_dasar_pipa, panjang_pipa, bts_tekanan_pori, waduk_id)
                                        VALUES
                                        (:nama, :elev_dasar_pipa, :panjang_pipa, :bts_tekanan_pori, :waduk_id)");
                $stmt->execute([
                    ':nama' => $form['nama'],
                    ':waduk_id' => $id,
                    ':elev_dasar_pipa' => empty($form['elev_dasar_pipa']) ? 0 : $form['elev_dasar_pipa'],
                    ':panjang_pipa' => empty($form['panjang_pipa']) ? 0 : $form['panjang_pipa'],
                    ':bts_tekanan_pori' => empty($form['bts_tekanan_pori']) ? 0 : $form['bts_tekanan_pori'],
                ]);

                // $this->flash->addMessage('messages', 'Piezometer Berhasil Ditambahkan');
                return $this->response->withRedirect($this->router->pathFor('waduk.detail', ['id' => $id], []));
            })->setName('waduk.piezometer.add');
        });
    });

})->add($adminAuthorizationMiddleware)->add($loggedinMiddleware);
