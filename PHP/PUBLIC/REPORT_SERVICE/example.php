<?php

/*
    TABLE EMEDDED APPROACH
*/

$array = array(
    "template" => "template/template.xml",
    "data_object" => array('title' => 'Example Title',
                        'author' => 'Simone Gosetto',
                        'process' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque pellentesque ante vel odio blandit, at bibendum turpis fermentum. Nunc auctor sagittis lectus et tincidunt. In feugiat a odio id blandit. Praesent lacinia in est a aliquam. Nunc massa nibh, facilisis sed venenatis a, ullamcorper non risus. Curabitur dignissim odio id gravida rutrum. Donec a urna a erat sodales facilisis. Fusce tristique pharetra mi quis sodales. Etiam rutrum sit amet purus sit amet convallis. Nam nunc purus, aliquet eget massa sit amet, feugiat congue nibh. Duis consequat lorem a porttitor viverra. Sed a dolor et urna laoreet aliquam nec quis justo. Duis sollicitudin varius scelerisque. Nullam aliquet libero et dapibus cursus. Ut porta elementum maximus. Curabitur eget est ac eros convallis viverra id a massa.',
                        'image' => 'https://www.google.it/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png',
                        'table_embedded' => array(
                            array("name" => "farina", "quantity" => 100, "perc" => 30),
                            array("name" => "olio", "quantity" => 70, "perc" => 20)
                        )
                    )
);

$param = json_encode($array);
Header("Location: SG_ReportService.php?ReportData=".$param);




/*
    TABLE GLOABL APPROACH
*/


$array = array(
    "template" => "template/template_2.xml",
    "data_object" => array(
                        array(
                            'title' => 'Example Title',
                            'author' => 'Simone Gosetto',
                            'process' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque pellentesque ante vel odio blandit, at bibendum turpis fermentum. Nunc auctor sagittis lectus et tincidunt. In feugiat a odio id blandit. Praesent lacinia in est a aliquam. Nunc massa nibh, facilisis sed venenatis a, ullamcorper non risus. Curabitur dignissim odio id gravida rutrum. Donec a urna a erat sodales facilisis. Fusce tristique pharetra mi quis sodales. Etiam rutrum sit amet purus sit amet convallis. Nam nunc purus, aliquet eget massa sit amet, feugiat congue nibh. Duis consequat lorem a porttitor viverra. Sed a dolor et urna laoreet aliquam nec quis justo. Duis sollicitudin varius scelerisque. Nullam aliquet libero et dapibus cursus. Ut porta elementum maximus. Curabitur eget est ac eros convallis viverra id a massa.',
                            'image' => 'https://www.google.it/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png',
                            'name' => 'farina',
                            'quantity' => 100,
                            'perc' => 30
                        ),
                        array(
                            'title' => 'Example Title',
                            'author' => 'Simone Gosetto',
                            'process' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque pellentesque ante vel odio blandit, at bibendum turpis fermentum. Nunc auctor sagittis lectus et tincidunt. In feugiat a odio id blandit. Praesent lacinia in est a aliquam. Nunc massa nibh, facilisis sed venenatis a, ullamcorper non risus. Curabitur dignissim odio id gravida rutrum. Donec a urna a erat sodales facilisis. Fusce tristique pharetra mi quis sodales. Etiam rutrum sit amet purus sit amet convallis. Nam nunc purus, aliquet eget massa sit amet, feugiat congue nibh. Duis consequat lorem a porttitor viverra. Sed a dolor et urna laoreet aliquam nec quis justo. Duis sollicitudin varius scelerisque. Nullam aliquet libero et dapibus cursus. Ut porta elementum maximus. Curabitur eget est ac eros convallis viverra id a massa.',
                            'image' => 'https://www.google.it/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png',
                            'name' => 'olio',
                            'quantity' => 70,
                            'perc' => 20
                        ),
                    )
);

$param = json_encode($array);
Header("Location: SG_ReportService.php?ReportData=".$param);


