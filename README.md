# TrackLeavingTrucks
This project takes and expands on wahts in my other TruckYardTracking repo. Instead of getting a list of what trucks are in the yard, it keeps track of trucks that were at this yard and at what time they left at the beginning of their day.
It starts by running the first PHP script trucksInYardAM.
This script first checks if it's the begininning of the day. If it is, it resets the spreadsheet and txt files being used as lists.
If it's not the begining of the day it adds the truck name to a txt file called trucksInYardllDay.txt. This file is used in the other script.
Then the trucksLeaving PHP script is run. It also checks the time of day so it can reset the processed truck list every morning.
It then checks if the trucks in trucksInYardllDay.txt have left the yard yet.
If they're no longer in the yard, it will add the truck to the processedTrucks.txt file so that that file can be used next time thism script is run to tell the script to ignore that truck.
The truck name is added to an excel spreadsheet along with the current time to keep track of what time the truck left the yard.
This project was much more complex than the TruckYardTracking project as this project had to keep track of much more variables.
It's also very useful to us as it helps us ensure that the times the drivers are reporting on their punch ins are accurate.
