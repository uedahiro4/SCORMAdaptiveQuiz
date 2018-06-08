1) 概要
　この活動モジュールは国立情報学研究所/National Institute of Informaticsが開発しました。
開発モジュールは、Quiz(Moodle Quiz Module)の結果から理解度を判定し、SCORMコンテンツ(Moodle SCORM Module)の、特定のコンテンツ項目(SCO: Sharable Contents Object)の学習状態値(lesson_status)を学習済みに変更します。
2) SCORM-Quizマッピング方式
　問題とコンテンツ項目の結びつけは、一般的には学習目的項目(Learning Objectives)を通して関連付けられますが、本来、プレテストに使用するMoodle Quiz(Moodle Quiz Module)とSCORMコンテンツ(Moodle SCORM Module)は関連しない独立したMoodle Activity Moduleですので、共通の学習目的項目データを持っていません。また、今回の開発モジュールは既存のSCORMコンテンツと既存の問題データベース(Moodle Question Database)を、できる限り変更しないことを前提としているため、意図的に関連性を持たせる工夫として、それぞれの既存の属性を使用してマッピングする方法を採用します。Moodle QuestionのIDとSCOのID(IMS Manifest内のidentifier)を直接マッピングすることも可能ですが、Question IDはMoodleでは自動採番されるため、より関連性の意味を管理・付与しやすいQuestion Category NameとSCOのidentifierをマッピング用のIDとするマッピング方式を採用します。
このSCO:identifierとQuestion:Categoryによるマッピングの方式は、既存のSCORMコンテンツはそのままのIMS Manifestを使用できるので全く変更する必要がなく、Quizを問題データベースのCategoryから選択した問題セットとして利用することにより、容易にSCO:identifierに基づいたCategoryに設定できるので、関連付けるためのワークフローからも適切だと考えられます。特に、今回の場合、プリテストの問題データベースは作成されていますが、まだ、QuestionにCategoryが設定されていないので、プリテストを作成する際にQuestion:Categoryを設定する作業が可能なことから発注要件に適合します。
開発モジュールでは、初期設定時に関連付けたいSCORMデータとQuizデータを選択すると、両方に共通するIDを自動的にマッピングIDとして抽出し、scormadaptivequiz_scoesテーブルに記載します。また、マッピングIDのQuiz結果の理解度を判断する基準値(閾値)も、このテーブルに記載します。
3) 主要機能/画面遷移図
　開発モジュールの主要機能は、SCORM-Quizマッピングテーブルを作成する「設定」機能と、Quiz結果をマッピング情報に基づいて判定し、結果をアニメーション・ドーナッツグラフとテーブル形式で表示する「判定結果表示」機能と、その判定に基づいてSCORMコンテンツの学習状態を受講済みに変更する「学習状態変更」機能から構成されます。
A)	設定機能: SCO:identifierとQuestion:Categoryのマッピング機能(教師/管理者用)
B)	判定結果表示機能:
(ア)	プレテスト(Moodle Quiz Module)の受講結果取得機能(学習者用)
(イ)	正解率集計と閾値からスキップ可能/不可SCOリスト作成機能(学習者用)
(ウ)	受講者へのスキップ可能/不可SCO結果提示機能(学習者用)
C)	学習状態変更: スキップ可能SCOの学習進捗値を受講済みに変更機能(学習者用)
