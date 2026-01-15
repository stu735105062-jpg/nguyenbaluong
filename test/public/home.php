<?php
require '../config/config.php';
require '../includes/load_data.php';
require '../includes/auth_check.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <link rel="stylesheet" href="../public/asset/css/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../public/asset/css/bootstrap-icons-1.11.3/font/bootstrap-icons.css">
    <link rel="shortcut icon" href="../public/asset/images/mortarboard.png">
</head>
<body class="d-flex flex-column min-vh-100">

<?php
    require '../includes/header.php';
    require '../models/restoreM.php';
?>

        <div class="container-fluid flex-grow-1">
        <div class="row">
            <div class="col md-12">
                <h2 class="mt-2">Student List 

                    <span class="position-absolute start-50 translate-middle-x">
                    <a href="../includes/export.php" class="btn fs-3 me-2">
                        <i class="bi bi-download"></i> Backup
                    </a>
                    <a href="" class="btn fs-3" data-bs-toggle="modal" data-bs-target="#restoreModal">
                        <i class="bi bi-upload"></i> restore
                    </a>
                </span>
         

                    <button class="btn btn-success float-end" data-bs-toggle="modal" data-bs-target="#mymodal">+ Add Student</button>
                </h2>
                <?php
                    require '../includes/alert.php';
                ?>

                <div class="table-responsive rounded-3" style="max-height: 700px; overflow-y: auto;">
                <table class="table table-striped table-bordered table-hover mb-0">
                    <tr class="table-dark text-light">
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Action</th>
                    </tr>

                    <?php while($row = mysqli_fetch_assoc($result)):?>

                    <tr>
                        <td><?= $row['id']  ?></td>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['email'] ?></td>
                        <td><?= $row['phone'] ?></td>
                        <td>
                            <a href="" class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#edit<?= $row['id']?>">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <a href="" class="btn btn-danger btn-sm me-2" data-bs-toggle="modal" data-bs-target="#delete<?= $row['id'] ?>">
                                <i class="bi bi-trash"></i>
                            </a>

                             <a href="../includes/report.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm">
                               <i class="bi bi-file-earmark-pdf"></i>
                            </a>

                            <?php require'../models/deleteM.php' ?>
                        </td>
                    </tr>
                    <?php require '../models/updateM.php' ?>
                    <?php endwhile; ?>

                </table>
                </div>

            </div>

        </div>

        <?php
            require '../models/addM.php'
        ?>

    </div>


<?php
    require '../includes/footer.php'
?>


</body>
<script src="../public/asset/js/toggle.js"></script>
<script src="../public/asset/css/bootstrap-5.3.3-dist/js/bootstrap.min.js"></script>
</html>