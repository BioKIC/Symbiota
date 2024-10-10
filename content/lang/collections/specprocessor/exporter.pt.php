<?php
/*
------------------
Language: Português (Portuguese)
Translated by: Google Translate (2024-09-13)
------------------
*/

$LANG['OCC_EXP_MAN'] = 'Gerente de Exportação de Ocorrências';
$LANG['NEW_RECORDS_PROC_STATUS'] = 'Novos registros não podem ter status de processamento não processado ou indefinido. Selecione um status de processamento válido.';
$LANG['EXP_TYPE'] = 'Tipo de exportação';
$LANG['CUSTOM_EXP'] = 'Exportação personalizada';
$LANG['GEO_EXP'] = 'Exportação de georreferência';
$LANG['EXPORT_EXPLAIN'] = 'Este módulo de download foi projetado para ajudar os gerentes de coleções na extração de dados de espécimes para importação para sistemas locais de gestão ou pesquisa.';
$LANG['MORE'] = 'mais informações';
$LANG['EXPORT_EXPLAIN_2'] = 'O módulo de exportação é particularmente útil para extrair dados que foram adicionados usando as ferramentas de digitalização integradas no portal web (crowdsourcing, OCR/NLP, entrada de dados básicos, etc.).					Os registros importados de um banco de dados local são vinculados ao registro primário por meio de um identificador único de amostra (código de barras, chave primária, UUID, etc.).					Este identificador é armazenado no banco de dados do portal web e dá aos gerentes de cobrança a capacidade de atualizar registros locais com informações adicionadas no portal web.					Novos registros digitalizados diretamente no portal web (por exemplo, imagem para registro de fluxo de trabalho de entrada de dados) terão um identificador exclusivo nulo, que identifica o registro como novo e ainda não sincronizado com o banco de dados central.					Quando novos registros são extraídos do portal, importados para o banco de dados central e, em seguida, o instantâneo de dados do portal é atualizado, o número de catálogo será usado para sincronizar automaticamente os registros de espécimes do portal com aqueles no banco de dados central. Observe que a sincronização só funcionará se o identificador primário for imposto como exclusivo (por exemplo, sem duplicatas) no banco de dados central local.';
$LANG['EXPORT_BATCH_GEO'] = 'Exportar dados georreferenciados em lote';
$LANG['EXPORT_BATCH_GEO_EXPLAIN_1'] = 'Este módulo extrai dados de coordenadas apenas para os registros que foram georreferenciados usando o';
$LANG['BATCH_GEO_TOOLS'] = 'ferramentas de georreferenciamento em lote';
$LANG['EXPORT_BATCH_GEO_EXPLAIN_2'] = 'ou as ferramentas da comunidade GeoLocate.					Esses downloads são especialmente adaptados para importar as novas coordenadas para o banco de dados local.					Caso nenhum registro tenha sido georreferenciado no portal, o arquivo de saída estará vazio.';
$LANG['PROCESSING_STATUS'] = 'Status de processamento';
$LANG['ALL_RECORDS'] = 'Todos os registros';
$LANG['COMPRESSION'] = 'Compressão';
$LANG['ARCHIVE_DATA_PACK'] = 'Arquivar pacote de dados (arquivo ZIP)';
$LANG['FILE_FORMAT'] = 'Formato de arquivo';
$LANG['CSV'] = 'Delimitado por vírgula (CSV)';
$LANG['TAB_DELIMITED'] = 'Delimitado por tabulação';
$LANG['CHAR_SET'] = 'Conjunto de caracteres';
$LANG['EXPORT_LACKING_GEO'] = 'Exportar amostras sem dados de georreferenciamento';
$LANG['EXPORT_LACKING_GEO_EXPLAIN'] = 'Este módulo extrai amostras que não possuem coordenadas decimais ou possuem coordenadas que precisam ser verificadas.					Este download resultará em um Darwin Core Archive contendo um arquivo CSV codificado em UTF-8 contendo apenas georreferenciamento de colunas de dados relevantes para as ocorrências. Por padrão, as ocorrências serão limitadas a registros contendo informações de localidade, mas sem coordenadas decimais.					Esta saída é particularmente útil para criar extratos de dados que serão georreferenciados usando ferramentas externas.';
$LANG['COORDINATES'] = 'Coordenadas';
$LANG['ARE_EMPTY'] = 'estão vazios (é nulo)';
$LANG['HAVE_VALUES'] = 'têm valores (por exemplo, precisam de verificação)';
$LANG['ADDITIONAL_FILTERS'] = 'Filtros<br/>adicionais';
$LANG['SELECT_FIELD'] = 'Selecione o nome do campo';
$LANG['DOWNLOAD_RECORDS'] = 'Baixar registros';
$LANG['DOWNLOAD_SPEC_RECORDS'] = 'Baixar registros de amostras';
$LANG['NEW_RECORDS_ONLY'] = 'Apenas novos registros';
$LANG['EG_IN_PORTAL'] = '(por exemplo, registros processados ​​no portal)';
$LANG['MORE_INFO'] = 'Mais informações';
$LANG['MORE_INFO_TEXT'] = 'Limite a novos registros inseridos e processados ​​diretamente no portal que ainda não foram importados e sincronizados com o banco de dados central. Evite importar registros esqueléticos não processados, pois as importações futuras envolverão uma coordenação de dados mais complexa.';
$LANG['TRAIT_FILTER'] = 'Filtro de característica de ocorrência<br/>';
$LANG['OR_SPEC_ATTRIBUTE'] = 'OU selecione um estado de atributo específico';
$LANG['HOLD_CTRL'] = 'Mantenha pressionado o controle (ctrl) ou o botão de comando para selecionar várias opções';
$LANG['STRUCTURE'] = 'Estrutura';
$LANG['SYMB_NATIVE'] = 'Simbiota Nativa';
$LANG['SYMB_NATIVE_EXPLAIN'] = 'Symbiota nativo é muito semelhante ao Darwin Core, exceto com a adição de alguns campos, como substrato, coletores associados e descrição literal.';
$LANG['DWC_EXPLAIN'] = 'Darwin Core é um padrão de intercâmbio endossado pelo TDWG especificamente para conjuntos de dados de biodiversidade.					Para mais informações, visite o <a href=';
$LANG['DATA_EXTENSIONS'] = 'Extensões de dados';
$LANG['INCLUDE_DET'] = 'incluir histórico de determinação';
$LANG['INCLUDE_IMAGES'] = 'incluir registros multimídia';
$LANG['INCLUDE_ATTRIBUTES'] = 'incluir atributos de característica de ocorrência (extensão MeasurementOrFact)';
$LANG['OUTPUT_COMPRESSED'] = 'A saída deve ser um arquivo compactado';
$LANG['ACCESS_DENIED'] = 'Acesso negado';

?>