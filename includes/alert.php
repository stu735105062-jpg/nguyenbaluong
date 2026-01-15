                <!-- Alert Messages -->
                <?php

                    // Display success message
                    if (isset($_SESSION['success'])) {
                        echo '<div class="flash-messages d-flex justify-content-center">';
                        echo '<div class="alert alert-success alert-dismissible fade show text-center w-50 py-3 px-4 shadow" role="alert" style="font-size: 1rem;">';
                        echo $_SESSION['success'];
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                        echo '</div>';
                        echo '</div>';
                        unset($_SESSION['success']);
                    }

                    // Display error message
                    if (isset($_SESSION['error'])) {
                        echo '<div class="flash-messages d-flex justify-content-center">';
                        echo '<div class="alert alert-danger alert-dismissible fade show text-center w-50 py-3 px-4 shadow" role="alert" style="font-size: 1rem;">';
                        echo $_SESSION['error'];
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                        echo '</div>';
                        echo '</div>';
                        unset($_SESSION['error']);
                    }
                    ?>