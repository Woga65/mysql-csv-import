[English Version](README_en.md)

# MySQL / PHP CSV-Daten-Import

## Die Aufgabenstellung

Eine fiktive Kundenverwaltungs-Software hält Kontaktdaten in einer MySQL-Datenbank vor.

Jeder Kontakt bekommt einen Vermerk seit wann er in der Datenbank geführt und wann
sein Datensatz zuletzt verändert wurde.

Folgende Kontaktdaten werden zusätzlich erfasst:

* Anrede
* Vorname
* Nachname
* Geburtsdatum 
* Land
* Email
* Telefonnummer
* Sprache

Um diese Kontakte mit externen Datenbeständen abgleichen und erweitern zu können, soll ein web-basiertes Tool zum 
Import von CSV-Dateien entwickelt werden.

Das Tool sollte *beliebig strukturierte* CSV-Dateien mit bis zu 200.000 Datensätzen importieren können.
Dabei musste zwischen neuen und bereits vorhandenen Kontakten unterschieden werden.
Während neue Kontakte dem Datenbestand lediglich hinzugefügt werden, sollten vorhandene Kontakte mit den neuen
Daten abgeglichen und gegebenenfalls aktualisiert werden.

Die zu importierenden Beispieldaten befinden sich bereits auf dem Server. Die Dateinamen sind

* data_01
* data_02
* data_03
* data_1000
* data_50000

Die Dateierweiterung .csv wird von der Import-Software intern angehängt und wird daher nicht im Eingabefeld "Input file" mit eingegeben.

## Die Umsetzung

Für die Lösung kommt objektorientiertes PHP mit einem Frontend in JavaScript zum Einsatz.

Der Fokus liegt auf:

* große Dateien können verarbeitet werden
* Duplikatserkennung ist möglich
* unterschiedliche Datenstrukturen in den Dateien können verarbeitet werden
* Steuerung erfolgt über eine Oberfläche 

