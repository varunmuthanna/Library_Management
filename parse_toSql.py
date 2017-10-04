# -*- coding: utf-8 -*-
"""
Created on Sun Mar 12 20:52:24 2017

@author: varun
"""

import csv

f = open('insert.sql', 'w')


bookreader = list(csv.reader(open('books.csv','r'),delimiter='\t'))
borrower = list(csv.reader(open('borrowers.csv','r'),delimiter=','))
ntuple_books = len(bookreader)
nattr_books = len(bookreader[0])
ntuple_borrower = len(borrower)
nattr_borrower = len(borrower[0])

borrowert = ""
bookt = ""
authort = ""
bookauthort = ""
dict_author = {}
dict_bookauthor = {}
author_id = 1

bookt = bookt + "INSERT INTO BOOK VALUES \n"

for i in range(1,ntuple_books):
    #print "("
    bookt = bookt + "("
    s = []
    id_list = []
    for j in range(0,nattr_books):
        if j == 3:
            s = bookreader[i][j].split(',')
            for k in range(0,len(s)):
                if dict_author.get(s[k],0) == 0:
                    dict_author[s[k]] = author_id
                    id_list.append(author_id)
                    author_id = author_id+1
                else:
                    id_list.append(dict_author[s[k]])
            dict_bookauthor[bookreader[i][0]] = id_list
            continue
        if j != nattr_books -1:
            temp = bookreader[i][j].replace("'","\\'")
            if j != 4:
                temp = temp.replace(".","")
            bookt = bookt + "'" + temp + "'" + ","
        else:
            bookt = bookt + bookreader[i][j]
    if i != ntuple_books-1:
        bookt = bookt + "),\n"
    else:
        bookt = bookt + ");\n"

f.write(bookt)
#print bookt

authort = authort + "INSERT INTO AUTHORS VALUES \n"
for key in dict_author:
    temp = key.replace("'","\\'")
    temp = temp.replace(".","")
    authort = authort + "(" +str(dict_author[key]) + "," + "'" +temp+"'" + "),\n"

authort = authort[:len(authort)-2] + ";\n"
f.write(authort)
#print authort

bookauthort = bookauthort + "INSERT INTO BOOK_AUTHORS VALUES \n"
for key in dict_bookauthor:
    values = dict_bookauthor[key]
    temp = {}
    for i in range(0,len(values)):
        if temp.get(str(values[i]),0) == 0:
            bookauthort = bookauthort + "(" + "'" + key + "'"+ "," +str(values[i]) +"),\n"
            temp[str(values[i])] = 100

bookauthort = bookauthort[:len(bookauthort)-2] + ";\n"
f.write(bookauthort)
#print bookauthort

borrowert = borrowert + "INSERT INTO BORROWER VALUES \n"

for i in range(1,ntuple_borrower):
    borrowert = borrowert + "("
    for j in range(0,nattr_borrower):
        if j != nattr_borrower -1:
            borrowert = borrowert +"'" +borrower[i][j]+"'" + ","
        else:
            borrowert = borrowert +"'" +borrower[i][j]+"'"
    if i != ntuple_borrower - 1:
        borrowert = borrowert + "),\n"
    else:
        borrowert = borrowert + ");"

f.write(borrowert)        
#print borrowert
f.close()
