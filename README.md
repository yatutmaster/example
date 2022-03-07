# Модуль по сбору статистики Laravel Modules
Суть логики. с агрегировать и сонхронизировать информацию для графиков. Это сырой демо код, но по нему можно понять приблизительно как я пишу.

# Всего четыре графика, описание одного из них.

Принцип агрегации данных: 

Отрезок времени на согласование, берется только из рабочего времени. То есть, допустим у нас рабочее время с 9 до 18 часов, документ был отправлен в 17 часов, и согласован на следующий рабочий день в 10 часов, то потраченное время на согласование составляет 2 часа. то есть учитывается только рабочее время. Еще пример, документ был отправлен в 19 часов, то есть в не раб время, и согласован в 20 часов, то есть отправка и ответ произошли в не рабочее время, тогда время на согласование равно нулю. 

Учет нерабочего времени следующий:

Суббота и воскресенье выходной.

Рабочее время с ПН по ЧТ с 9 до 18 часов, в пятницу  с 9 до 17 часов.

Выходные 

'01-01',//новогодние каникулы

'01-02',//новогодние каникулы

'01-03',//новогодние каникулы

'01-04',//новогодние каникулы

'01-05',//новогодние каникулы

'01-06',//новогодние каникулы

'01-07',//Рождество Христово

'01-08',//новогодние каникулы

'02-23',//день защитника Отечества

'03-08',//8 марта

'05-01',//Праздник Весны и Труда

'05-09',//День Победы

'06-12',//День России

'11-04',//День народного единства


Commands: 

statistic:document-sync  {type=all|average-approval-time} - Команда агрегирует и синхронизирует данные в бд. Первый запуск загружает все данные по статистике, и каждый последующий запуск синхронизирует данные которые были обновлены или созданы. Эту же команду будем потом запускать в тасках. type указывает, тип графика по статистике, то есть можно по отдельности запускать для каждого графика или все сразу.

statistic:document-refresh  {type=all|average-approval-time} Очищает данные статистики и запускает синхронизацию. type указывает, тип графика по статистике, то есть можно по отдельности запускать для каждого графика или все сразу.
