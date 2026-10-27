<?php

class Attendance {
    private $conn;
    private $table = "attendance";
    private $userId;

    public $attendance_id;
    public $subject_id;
    public $attendance_date;
    public $status;
    public $reason;
    public $photo_path;

    public function __construct($db, $userId) {
        $this->conn = $db;
        $this->userId = (int) $userId;
    }

    private function writeLog($action, $details) {
        $stmt = $this->conn->prepare(
            "INSERT INTO activity_logs (user_id, action_type, details)
             VALUES (:user_id, :action_type, :details)"
        );
        $stmt->execute([
            ":user_id" => $this->userId,
            ":action_type" => $action,
            ":details" => $details,
        ]);
    }

    // TRANSACTION 1: Save the attendance record and its audit log together.
    public function addAttendance() {
        $query = "INSERT INTO " . $this->table . "
                  (user_id, subject_id, attendance_date, status, reason, photo_path)
                  VALUES (:user_id, :subject_id, :attendance_date, :status, :reason, :photo_path)";

        $this->reason = htmlspecialchars(strip_tags($this->reason ?? ''));

        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":user_id" => $this->userId,
                ":subject_id" => $this->subject_id,
                ":attendance_date" => $this->attendance_date,
                ":status" => $this->status,
                ":reason" => $this->reason,
                ":photo_path" => $this->photo_path,
            ]);

            $attendanceId = $this->conn->lastInsertId();
            $this->writeLog("CREATE", "Created attendance record #{$attendanceId}");
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("addAttendance error: " . $e->getMessage());
            return false;
        }
    }

    
    public function getAttendances() {
        $query = "SELECT a.*, s.subject_code, s.subject_name
                  FROM " . $this->table . " a
                  LEFT JOIN subjects s ON a.subject_id = s.subject_id
                  WHERE a.user_id = :user_id
                  ORDER BY a.attendance_date DESC, a.attendance_id DESC";

        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute([":user_id" => $this->userId]);
            return $stmt;
        } catch (PDOException $e) {
            error_log("getAttendances error: " . $e->getMessage());
            return false;
        }
    }

    
    public function getAttendanceById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE attendance_id = :id AND user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindValue(":user_id", $this->userId, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getAttendanceById error: " . $e->getMessage());
            return false;
        }
    }

    // TRANSACTION 2: Lock and update only this user's record, then log the change.
    public function updateAttendance($id) {
        $query = "UPDATE " . $this->table . "
                  SET subject_id = :subject_id,
                      attendance_date = :attendance_date,
                      status = :status,
                      reason = :reason,
                      photo_path = :photo_path
                  WHERE attendance_id = :id AND user_id = :user_id";

        $this->reason = htmlspecialchars(strip_tags($this->reason ?? ''));

        try {
            $this->conn->beginTransaction();

            $check = $this->conn->prepare(
                "SELECT attendance_id FROM " . $this->table . "
                 WHERE attendance_id = :id AND user_id = :user_id
                 FOR UPDATE"
            );
            $check->execute([
                ":id" => $id,
                ":user_id" => $this->userId,
            ]);
            if (!$check->fetchColumn()) {
                $this->conn->rollBack();
                return false;
            }

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":subject_id" => $this->subject_id,
                ":attendance_date" => $this->attendance_date,
                ":status" => $this->status,
                ":reason" => $this->reason,
                ":photo_path" => $this->photo_path,
                ":id" => $id,
                ":user_id" => $this->userId,
            ]);

            $this->writeLog("UPDATE", "Updated attendance record #{$id}");
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("updateAttendance error: " . $e->getMessage());
            return false;
        }
    }

    // TRANSACTION 3: Lock and delete only this user's record, then log the change.
    public function deleteAttendance($id) {
        $query = "DELETE FROM " . $this->table . " WHERE attendance_id = :id AND user_id = :user_id";

        try {
            $this->conn->beginTransaction();

            $check = $this->conn->prepare(
                "SELECT attendance_id FROM " . $this->table . "
                 WHERE attendance_id = :id AND user_id = :user_id
                 FOR UPDATE"
            );
            $check->execute([
                ":id" => $id,
                ":user_id" => $this->userId,
            ]);
            if (!$check->fetchColumn()) {
                $this->conn->rollBack();
                return false;
            }

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":id" => $id,
                ":user_id" => $this->userId,
            ]);

            $this->writeLog("DELETE", "Deleted attendance record #{$id}");
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("deleteAttendance error: " . $e->getMessage());
            return false;
        }
    }

    
    public function searchAttendances($keyword) {
        $query = "SELECT a.*, s.subject_code, s.subject_name
                  FROM " . $this->table . " a
                  LEFT JOIN subjects s ON a.subject_id = s.subject_id
                  WHERE a.user_id = :user_id
                    AND (s.subject_code LIKE :keyword
                     OR s.subject_name LIKE :keyword
                     OR a.status LIKE :keyword
                     OR a.reason LIKE :keyword
                     OR a.attendance_date LIKE :keyword)
                  ORDER BY a.attendance_date DESC, a.attendance_id DESC";

        $stmt = $this->conn->prepare($query);
        $search = "%{$keyword}%";

        try {
            $stmt->execute([
                ":user_id" => $this->userId,
                ":keyword" => $search,
            ]);
            return $stmt;
        } catch (PDOException $e) {
            error_log("searchAttendances error: " . $e->getMessage());
            return false;
        }
    }
}
?>
