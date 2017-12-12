ru.cherezweb.hosting
===================
В этом репозитории находится код хостинга на базе Symfony2. Код раскрыт в связи с закрытием проекта "Хостинг ЧерезВеб". Идея проекта заключалась в хостинге на базе VPS от DigitalOcean. В теории с ростом числа клиентов, хостинг мог бы сам через API от DigitalOcean подключать/отключать новые VPS, автоматически их при этом конфигурируя. Функционал работы с API не был реализован, зато многие основные базовые функции хостинга были реализованы и вполне успешно работали.

Видео с демонстрацией работы кода: https://www.youtube.com/watch?v=Wg_pc1hAKK8

----------

Общее описание
-------------
Проект состоит из 5 подпроектов:

 - **ru.cherezweb.hosting.main** - контрольная панель хостинга, центр всей логики, представляет собой обычный проект на Symfony2;
 - **ru.cherezweb.hosting.srv** - код, который должен находиться на хостинг-серверах (где хостятся сайты клиентов), в readme описана конфигурация, для которой предназначен код и инсталлятор, есть бранч, в котором хранится еще одна версия кода (есть версия для php5.3 и для php5.5a);
 - **ru.cherezweb.hosting.srv.starter** - компонента от ru.cherezweb.hosting.srv, но она вынесена в отдельную директорию, так как она написана на java;
 - **ru.cherezweb.hosting.sql** - код для привязки phpMyAdmin сервера ко всем хостинг-серверам через ssh, это не лучшее решение, но оно позволило с одного phpMyAdmin видеть все сервера хостинга без открытия удаленного доступа в mysql;
 - **ru.cherezweb.hosting.dns** - компонента для нейм-серверов, основная цель - загружать DNS-записи с *ru.cherezweb.hosting.main* на нейм-серверы.

Площадка - это базовое понятие в терминологии данного хостинга, именно за неё платит клиент. С технической точки площадка - это учетная запись Linux на одном из хостинг-серверов. Стоимость площадки может определяться производительностью сервера и квотой на место для данной площадки.

# ru.cherezweb.hosting.main

Основная логика заключается в том, что на главном сервере (ru.cherezweb.hosting.main) хранится состояние хостинг-серверов в виде записей в базе: площадки, ftp-доступы, базы данных, привязки доменов, DNS-записи. При изменении состояния-хостинг сервера создаются задания, выполнение которых позволяет синхронизировать состояние хостинг-серверов с теми записями, которые присутствуют на главном сервере. Выполнение заданий очуществляется по расписанию (через cron выполняется консольная команда для Symfony). Чтобы избежать повторных запусков обработчика заданий используются блокировки, таким образом, если заданий за минуту накопилось слишком много, и они не успевают обработаться за минуту, то следующий запуск обработчика не произойдет до тех пор, пока не закончит свое выполнение предыдущий обработчик. Задания хранят информацию об успешности их выполнения, если при выполнении задания произошла ошибка, следующие задания не будут выполняться, а администратору будет отправлено уведомление, такой подход позволяет избежать критической рассинхронизации в состояниях главного и хостинг-серверов.






